<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use App\Models\Rental;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class PaymentManager extends Component
{
    use WithPagination;

    public ?int $rental_id = null;
    public $amount = 0;
    public string $method = 'cash';
    public string $type = 'payment';
    public string $date = '';
    public string $notes = '';

    public string $search = '';
    public string $filterMethod = '';
    public string $from = '';
    public string $to = '';
    public $paymentProof = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMethod(): void
    {
        $this->resetPage();
    }

    public function recordPayment(): void
    {
        $this->validate([
            'rental_id' => ['required', 'exists:rentals,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'in:cash,card,transfer,check'],
            'type' => ['required', 'in:payment,deposit,refund'],
            'date' => ['required', 'date'],
            'paymentProof.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $rental = Rental::findOrFail($this->rental_id);
        $this->authorize('view', $rental);

        if ($this->type === 'refund') {
            if ((int) $this->amount > $rental->paid_amount) {
                session()->flash('error', 'Le remboursement dépasse le montant déjà payé.');
                return;
            }
            $rental->update(['paid_amount' => $rental->paid_amount - (int) $this->amount]);
        } else {
            $newPaid = $rental->paid_amount + (int) $this->amount;
            if ($newPaid > $rental->total) {
                session()->flash('error', 'Le total payé dépasse le montant de la location.');
                return;
            }
            $rental->update(['paid_amount' => $newPaid, 'payment_method' => $this->method]);
        }

        $proofPaths = [];
        foreach ($this->paymentProof as $proof) {
            $proofPaths[] = \Illuminate\Support\Facades\Storage::disk('public')->putFile('payments', $proof);
        }

        $payment = Payment::create([
            'store_id' => $rental->store_id ?? StoreContext::id(),
            'rental_id' => $rental->id,
            'user_id' => auth()->id(),
            'reference' => ReferenceGenerator::reference($this->type === 'refund' ? 'RMB' : 'PAY', Payment::class),
            'amount' => (int) $this->amount,
            'method' => $this->method,
            'type' => $this->type,
            'date' => $this->date,
            'notes' => $this->notes ?: null,
            'proof_image_paths' => $proofPaths ?: null,
        ]);

        AuditLogger::created($payment, 'payment.created');

        $this->reset(['rental_id', 'amount', 'method', 'type', 'date', 'notes', 'paymentProof']);
        $this->method = 'cash';
        $this->type = 'payment';
        session()->flash('status', 'Paiement enregistré.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $payments = Payment::query()
            ->with(['rental.customer', 'user'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', '%'.$this->search.'%')
                    ->orWhereHas('rental.customer', fn ($c) => $c->where('first_name', 'like', '%'.$this->search.'%')->orWhere('last_name', 'like', '%'.$this->search.'%'));
            }))
            ->when($this->filterMethod, fn ($q) => $q->where('method', $this->filterMethod))
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->latest()
            ->paginate(15);

        $rentals = Rental::query()
            ->with('customer')
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->whereIn('status', ['reserved', 'active'])
            ->whereColumn('paid_amount', '<', 'total')
            ->latest()
            ->get();

        $methodLabels = Payment::methodLabels();
        $typeLabels = Payment::typeLabels();

        $today = (int) Payment::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->where('type', '!=', 'refund')->whereDate('date', now()->toDateString())->sum('amount');
        $month = (int) Payment::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->where('type', '!=', 'refund')->whereYear('date', now()->year)->whereMonth('date', now()->month)->sum('amount');
        $total = (int) Payment::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->where('type', '!=', 'refund')->sum('amount');
        $refunded = (int) Payment::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->where('type', 'refund')->sum('amount');

        return view('livewire.payments.payment-manager', compact('payments', 'rentals', 'methodLabels', 'typeLabels', 'today', 'month', 'total', 'refunded'));
    }
}