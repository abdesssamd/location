<?php

namespace App\Livewire\Purchases;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Réception d'un achat fournisseur : à l'inverse d'une vente, chaque ligne
 * réintègre le stock (products.quantity) au moment de l'enregistrement.
 */
#[Layout('components.layouts.app')]
class PurchaseForm extends Component
{
    public ?int $supplier_id = null;
    public string $supplier_search = '';
    public bool $showNewSupplier = false;
    public string $new_supplier_name = '';
    public string $new_supplier_phone = '';

    public string $product_search = '';

    /** @var array<int, array{product_id:int, name:string, reference:string, quantity:int, unit_cost:int}> */
    public array $items = [];

    public string $payment_method = 'cash';
    public $paid_amount = 0;
    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', Purchase::class);
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        foreach ($this->items as &$item) {
            if ($item['product_id'] === $product->id) {
                $item['quantity']++;

                return;
            }
        }
        unset($item);

        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'quantity' => 1,
            'unit_cost' => 0,
        ];

        $this->product_search = '';
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $this->items[$index]['quantity'] = max(1, $quantity);
    }

    public function updateUnitCost(int $index, int $unitCost): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $this->items[$index]['unit_cost'] = max(0, $unitCost);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function selectSupplier(int $supplierId): void
    {
        $this->supplier_id = $supplierId;
        $this->supplier_search = '';
    }

    public function createSupplier(): void
    {
        $this->validate([
            'new_supplier_name' => ['required', 'string', 'max:255'],
        ]);

        $supplier = Supplier::create([
            'store_id' => StoreContext::id(),
            'name' => $this->new_supplier_name,
            'phone' => $this->new_supplier_phone ?: null,
        ]);

        $this->supplier_id = $supplier->id;
        $this->showNewSupplier = false;
        $this->new_supplier_name = '';
        $this->new_supplier_phone = '';
    }

    public function getTotalProperty(): int
    {
        return (int) collect($this->items)->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);
    }

    public function save(): void
    {
        $this->authorize('create', Purchase::class);

        if (empty($this->items)) {
            session()->flash('error', 'Ajoutez au moins un article reçu.');

            return;
        }

        $this->validate([
            'payment_method' => ['required', 'in:cash,card,transfer,check'],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $storeId = StoreContext::id();

        abort_if($storeId === null, 403, 'Aucun magasin associé à votre compte.');

        $purchase = DB::transaction(function () use ($storeId) {
            $productIds = collect($this->items)->pluck('product_id')->all();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $total = $this->total;

            $purchase = Purchase::create([
                'store_id' => $storeId,
                'supplier_id' => $this->supplier_id,
                'user_id' => auth()->id(),
                'reference' => ReferenceGenerator::reference('ACH', Purchase::class),
                'status' => Purchase::STATUS_RECEIVED,
                'subtotal' => $total,
                'total' => $total,
                'paid_amount' => min((int) $this->paid_amount, $total),
                'payment_method' => $this->payment_method,
                'notes' => $this->notes ?: null,
                'date' => now()->toDateString(),
            ]);

            foreach ($this->items as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    continue;
                }

                PurchaseItem::create([
                    'store_id' => $storeId,
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['quantity'] * $item['unit_cost'],
                ]);

                // Réception fournisseur : le parc grandit, contrairement à une vente.
                $product->increment('quantity', $item['quantity']);

                StockMovement::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'purchase',
                    'quantity' => $item['quantity'],
                    'reason' => 'Achat '.$purchase->reference,
                    'date' => now(),
                ]);
            }

            if ($purchase->paid_amount > 0) {
                \App\Models\Payment::create([
                    'store_id' => $storeId,
                    'purchase_id' => $purchase->id,
                    'user_id' => auth()->id(),
                    'reference' => ReferenceGenerator::reference('PAY', \App\Models\Payment::class),
                    'amount' => $purchase->paid_amount,
                    'method' => $this->payment_method,
                    'type' => 'payment',
                    'date' => now()->toDateString(),
                ]);
            }

            AuditLogger::created($purchase, 'purchase.created');

            return $purchase;
        });

        session()->flash('status', 'Achat '.$purchase->reference.' enregistré, stock mis à jour.');
        $this->redirect(route('purchases.show', $purchase), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        $suppliers = $this->supplier_search
            ? Supplier::query()
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->where('name', 'like', '%'.$this->supplier_search.'%')
                ->limit(8)
                ->get()
            : collect();

        $products = $this->product_search
            ? Product::query()
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->product_search.'%')
                        ->orWhere('reference', 'like', '%'.$this->product_search.'%')
                        ->orWhere('barcode', 'like', '%'.$this->product_search.'%');
                })
                ->limit(8)
                ->get()
            : collect();

        $selectedSupplier = $this->supplier_id ? Supplier::find($this->supplier_id) : null;

        return view('livewire.purchases.purchase-form', compact('suppliers', 'products', 'selectedSupplier'));
    }
}
