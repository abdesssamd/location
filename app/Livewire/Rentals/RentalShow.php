<?php

namespace App\Livewire\Rentals;

use App\Models\Product;
use App\Models\Rental;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class RentalShow extends Component
{
    use WithFileUploads;

    public Rental $rental;

    public $paid_amount = 0;
    public string $payment_method = 'cash';
    public string $notes = '';
    public $paymentProof = [];
    public bool $showReturnPanel = false;

    /** @var array<int, array<string, mixed>> */
    public array $returnItems = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public $returnPhotos = [];

    public function mount(Rental $rental): void
    {
        $this->rental = $rental->load(['customer', 'user', 'items.product']);
        $this->paid_amount = $rental->paid_amount;
        $this->payment_method = $rental->payment_method ?? 'cash';
        $this->notes = $rental->notes ?? '';

        $this->returnItems = $this->rental->items->map(fn ($item) => [
            'rental_item_id' => $item->id,
            'condition' => $item->return_condition ?? 'good',
            'damage_fee' => (int) $item->return_damage_fee,
            'notes' => (string) ($item->return_notes ?? ''),
        ])->values()->toArray();
    }

    public function checkout(): void
    {
        if ($this->rental->status !== 'reserved') {
            return;
        }

        foreach ($this->rental->items as $item) {
            StockMovement::create([
                'store_id' => $this->rental->store_id ?? StoreContext::id(),
                'product_id' => $item->product_id,
                'user_id' => auth()->id(),
                'type' => 'out',
                'quantity' => -$item->quantity,
                'reason' => 'Sortie location '.$this->rental->reference,
                'date' => now(),
            ]);
        }

        $old = $this->rental->getAttributes();
        $this->rental->update(['status' => 'active']);
        AuditLogger::updated($this->rental, $old, 'rental.checkout');
        session()->flash('status', 'Location démarrée.');
    }

    public function complete(): void
    {
        if (! in_array($this->rental->status, ['reserved', 'active'], true)) {
            return;
        }

        $this->validate([
            'returnItems.*.condition' => ['required', 'in:good,damaged,lost,cleaning'],
            'returnItems.*.damage_fee' => ['nullable', 'integer', 'min:0'],
            'returnItems.*.notes' => ['nullable', 'string'],
            'returnPhotos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $lateDays = 0;
        $end = $this->rental->end_date;
        if (now()->startOfDay()->gt($end->copy()->startOfDay())) {
            $lateDays = max(1, $end->startOfDay()->diffInDays(now()->startOfDay()));
        }
        $store = \App\Models\Store::find($this->rental->store_id);
        $lateFeePerDay = (int) ($store?->late_fee_per_day ?? 0);
        $lateFee = $lateDays * $lateFeePerDay;

        $this->persistReturnDetails();
        $this->restoreStock();

        $old = $this->rental->getAttributes();
        $this->rental->update([
            'status' => 'completed',
            'actual_return_date' => now()->toDateString(),
            'late_fee' => $lateFee,
        ]);
        AuditLogger::updated($this->rental, $old, 'rental.completed');

        if ($lateFee > 0) {
            $payment = \App\Models\Payment::create([
                'store_id' => $this->rental->store_id ?? StoreContext::id(),
                'rental_id' => $this->rental->id,
                'user_id' => auth()->id(),
                'reference' => \App\Services\ReferenceGenerator::reference('PAY', \App\Models\Payment::class),
                'amount' => $lateFee,
                'method' => 'cash',
                'type' => 'penalty',
                'date' => now()->toDateString(),
                'notes' => 'Pénalité de retard ('.$lateDays.' j × '.$lateFeePerDay.' DA) '.$this->rental->reference,
            ]);
            AuditLogger::created($payment, 'payment.created');
        }

        session()->flash('status', 'Location terminée, stock restitué.'.(($lateFee > 0) ? ' Pénalité de retard : '.$lateFee.' DA.' : ''));
    }

    public function cancel(): void
    {
        if ($this->rental->status !== 'reserved') {
            session()->flash('error', 'Seule une réservation peut être annulée.');

            return;
        }

        $this->releaseStock();

        $old = $this->rental->getAttributes();
        $this->rental->update(['status' => 'cancelled']);
        AuditLogger::updated($this->rental, $old, 'rental.cancelled');
        session()->flash('status', 'Réservation annulée.');
    }

    /**
     * Restitue le stock des articles d'une location (annulation).
     */
    protected function releaseStock(): void
    {
        foreach ($this->rental->items as $item) {
            $product = Product::find($item->product_id);
            if (! $product) {
                continue;
            }

            $product->increment('quantity', $item->quantity);

            StockMovement::create([
                'store_id' => $product->store_id ?? StoreContext::id(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $item->quantity,
                'reason' => 'Annulation réservation '.$this->rental->reference,
                'date' => now(),
            ]);
        }
    }

    public function recordPayment(): void
    {
        $this->validate([
            'paid_amount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,card,transfer,check'],
            'paymentProof.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $amount = (int) $this->paid_amount;
        $newPaid = $this->rental->paid_amount + $amount;

        if ($newPaid > $this->rental->total) {
            session()->flash('error', 'Le total payé dépasse le montant de la location.');
            return;
        }

        $proofPaths = [];
        foreach ($this->paymentProof as $proof) {
            $proofPaths[] = Storage::disk('public')->putFile('payments', $proof);
        }

        $old = $this->rental->getAttributes();
        $this->rental->update([
            'paid_amount' => $newPaid,
            'payment_method' => $this->payment_method,
        ]);
        AuditLogger::updated($this->rental, $old, 'rental.payment');

        $payment = \App\Models\Payment::create([
            'store_id' => $this->rental->store_id ?? StoreContext::id(),
            'rental_id' => $this->rental->id,
            'user_id' => auth()->id(),
            'reference' => \App\Services\ReferenceGenerator::reference('PAY', \App\Models\Payment::class),
            'amount' => $amount,
            'method' => $this->payment_method,
            'type' => 'payment',
            'date' => now()->toDateString(),
            'notes' => 'Encaissement depuis la location '.$this->rental->reference,
            'proof_image_paths' => $proofPaths ?: null,
        ]);
        AuditLogger::created($payment, 'payment.created');

        $this->reset('paymentProof');
        session()->flash('status', 'Paiement de '.number_format($amount, 0, ',', ' ').' DA enregistré.');
    }

    protected function persistReturnDetails(): void
    {
        $indexed = collect($this->returnItems)->keyBy('rental_item_id');

        $photoPaths = [];
        foreach ($this->returnPhotos as $photo) {
            $photoPaths[] = Storage::disk('public')->putFile('returns', $photo);
        }

        foreach ($this->rental->items as $item) {
            $data = $indexed->get($item->id);
            if (! $data) {
                continue;
            }

            $item->update([
                'return_condition' => $data['condition'] ?? 'good',
                'return_damage_fee' => (int) ($data['damage_fee'] ?? 0),
                'return_notes' => $data['notes'] ?: null,
                'return_image_paths' => $photoPaths ?: null,
            ]);
        }

        $damageTotal = (int) $this->rental->items()->sum('return_damage_fee');
        if ($damageTotal > 0) {
            $payment = \App\Models\Payment::create([
                'store_id' => $this->rental->store_id ?? StoreContext::id(),
                'rental_id' => $this->rental->id,
                'user_id' => auth()->id(),
                'reference' => \App\Services\ReferenceGenerator::reference('PAY', \App\Models\Payment::class),
                'amount' => $damageTotal,
                'method' => 'cash',
                'type' => 'payment',
                'date' => now()->toDateString(),
                'notes' => 'Frais de dommage enregistrés au retour '.$this->rental->reference,
            ]);
            AuditLogger::created($payment, 'payment.created');
        }
    }

    protected function restoreStock(): void
    {
        $indexed = collect($this->returnItems)->keyBy('rental_item_id');

        foreach ($this->rental->items as $item) {
            $product = Product::find($item->product_id);
            if (! $product) {
                continue;
            }

            $condition = $indexed->get($item->id)['condition'] ?? 'good';
            if ($condition === 'lost') {
                $product->decrement('quantity', $item->quantity);
                $product->status = Product::STATUS_LOST;
                $product->save();

                StockMovement::create([
                    'store_id' => $product->store_id ?? StoreContext::id(),
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'lost',
                    'quantity' => -$item->quantity,
                    'reason' => 'Perte au retour '.$this->rental->reference,
                    'date' => now(),
                ]);

                continue;
            }

            $product->status = match ($condition) {
                'damaged' => Product::STATUS_DAMAGED,
                'cleaning' => Product::STATUS_CLEANING,
                default => ($product->quantity > 0 ? Product::STATUS_AVAILABLE : Product::STATUS_OFFLINE),
            };
            $product->save();

            // Restituer le stock disponible (sauf perte déjà gérée plus haut)
            $product->increment('quantity', $item->quantity);

            StockMovement::create([
                'store_id' => $product->store_id ?? StoreContext::id(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $item->quantity,
                'reason' => 'Retour location '.$this->rental->reference,
                'date' => now(),
            ]);
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.rentals.rental-show', [
            'statusLabels' => Rental::statusLabels(),
            'remaining' => $this->rental->remaining,
        ]);
    }
}