<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Vente rapide d'articles du stock. Contrairement à une location, une vente
 * retire l'unité définitivement : le parc (products.quantity) est décrémenté
 * au moment de l'encaissement, pas seulement l'engagement par dates.
 */
#[Layout('components.layouts.app')]
class SalePos extends Component
{
    public ?int $customer_id = null;
    public string $customer_search = '';
    public bool $showNewCustomer = false;
    public string $new_first_name = '';
    public string $new_last_name = '';
    public string $new_phone = '';

    public string $product_search = '';

    /** @var array<int, array{product_id:int, name:string, reference:string, quantity:int, unit_price:int, available:int}> */
    public array $items = [];

    public $discount = 0;
    public string $payment_method = 'cash';
    public $paid_amount = 0;
    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', Sale::class);
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        foreach ($this->items as &$item) {
            if ($item['product_id'] === $product->id) {
                if ($item['quantity'] >= $item['available']) {
                    session()->flash('error', 'Stock insuffisant pour « '.$product->name.' ».');

                    return;
                }
                $item['quantity']++;

                return;
            }
        }
        unset($item);

        if ($product->quantity < 1) {
            session()->flash('error', 'Aucun stock disponible pour « '.$product->name.' ».');

            return;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'quantity' => 1,
            'unit_price' => (int) ($product->sale_price ?: $product->rental_price),
            'available' => (int) $product->quantity,
        ];

        $this->product_search = '';
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $quantity = max(1, $quantity);

        if ($quantity > $this->items[$index]['available']) {
            session()->flash('error', 'Stock insuffisant : '.$this->items[$index]['available'].' disponible(s).');
            $quantity = $this->items[$index]['available'];
        }

        $this->items[$index]['quantity'] = $quantity;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function selectCustomer(int $customerId): void
    {
        $this->customer_id = $customerId;
        $this->customer_search = '';
    }

    public function createCustomer(): void
    {
        $this->validate([
            'new_first_name' => ['required', 'string', 'max:255'],
            'new_last_name' => ['required', 'string', 'max:255'],
            'new_phone' => ['required', 'string', 'max:30'],
        ]);

        $customer = Customer::create([
            'store_id' => StoreContext::id(),
            'first_name' => $this->new_first_name,
            'last_name' => $this->new_last_name,
            'phone' => $this->new_phone,
        ]);

        $this->customer_id = $customer->id;
        $this->showNewCustomer = false;
        $this->new_first_name = '';
        $this->new_last_name = '';
        $this->new_phone = '';
    }

    public function getSubtotalProperty(): int
    {
        return (int) collect($this->items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
    }

    public function getTotalProperty(): int
    {
        return max(0, $this->subtotal - (int) $this->discount);
    }

    public function checkout(): void
    {
        $this->authorize('create', Sale::class);

        if (empty($this->items)) {
            session()->flash('error', 'Ajoutez au moins un article à vendre.');

            return;
        }

        $this->validate([
            'payment_method' => ['required', 'in:cash,card,transfer,check'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $storeId = StoreContext::id();

        abort_if($storeId === null, 403, 'Aucun magasin associé à votre compte.');

        $sale = DB::transaction(function () use ($storeId) {
            // Revalidation et verrou dans la transaction : deux ventes simultanées
            // du dernier exemplaire ne peuvent pas passer toutes les deux.
            $productIds = collect($this->items)->pluck('product_id')->all();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($this->items as $item) {
                $product = $products->get($item['product_id']);

                if (! $product || $product->quantity < $item['quantity']) {
                    throw new \RuntimeException('Stock insuffisant pour « '.($product?->name ?? $item['name']).' ».');
                }
            }

            $subtotal = $this->subtotal;
            $total = $this->total;

            $sale = Sale::create([
                'store_id' => $storeId,
                'customer_id' => $this->customer_id,
                'user_id' => auth()->id(),
                'reference' => ReferenceGenerator::reference('VEN', Sale::class),
                'status' => Sale::STATUS_COMPLETED,
                'subtotal' => $subtotal,
                'discount' => (int) $this->discount,
                'total' => $total,
                'paid_amount' => min((int) $this->paid_amount, $total) ?: $total,
                'payment_method' => $this->payment_method,
                'notes' => $this->notes ?: null,
                'date' => now()->toDateString(),
            ]);

            foreach ($this->items as $item) {
                $product = $products->get($item['product_id']);

                SaleItem::create([
                    'store_id' => $storeId,
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);

                // La vente retire l'unité définitivement, contrairement à une
                // location qui la rend disponible après retour.
                $product->decrement('quantity', $item['quantity']);

                StockMovement::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'reason' => 'Vente '.$sale->reference,
                    'date' => now(),
                ]);
            }

            if ($sale->paid_amount > 0) {
                \App\Models\Payment::create([
                    'store_id' => $storeId,
                    'sale_id' => $sale->id,
                    'user_id' => auth()->id(),
                    'reference' => ReferenceGenerator::reference('PAY', \App\Models\Payment::class),
                    'amount' => $sale->paid_amount,
                    'method' => $this->payment_method,
                    'type' => 'payment',
                    'date' => now()->toDateString(),
                ]);
            }

            AuditLogger::created($sale, 'sale.created');

            return $sale;
        });

        session()->flash('status', 'Vente '.$sale->reference.' enregistrée.');
        $this->redirect(route('sales.show', $sale), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        $customers = $this->customer_search
            ? Customer::query()
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->customer_search.'%')
                        ->orWhere('phone', 'like', '%'.$this->customer_search.'%');
                })
                ->limit(8)
                ->get()
            : collect();

        $products = $this->product_search
            ? Product::query()
                ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                ->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->product_search.'%')
                        ->orWhere('reference', 'like', '%'.$this->product_search.'%')
                        ->orWhere('barcode', 'like', '%'.$this->product_search.'%');
                })
                ->limit(8)
                ->get()
            : collect();

        $selectedCustomer = $this->customer_id ? Customer::find($this->customer_id) : null;

        return view('livewire.sales.sale-pos', compact('customers', 'products', 'selectedCustomer'));
    }
}
