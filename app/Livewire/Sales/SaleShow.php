<?php

namespace App\Livewire\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SaleShow extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $this->authorize('view', $sale);
        $this->sale = $sale->load(['customer', 'user', 'items.product', 'payments']);
    }

    /**
     * Annule la vente et restitue le stock : contrairement à une location
     * qui peut clôturer normalement, une vente n'a que ce chemin de retour.
     */
    public function cancel(): void
    {
        $this->authorize('cancel', $this->sale);

        if ($this->sale->status !== Sale::STATUS_COMPLETED) {
            session()->flash('error', 'Cette vente est déjà annulée.');

            return;
        }

        foreach ($this->sale->items as $item) {
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
                'reason' => 'Annulation vente '.$this->sale->reference,
                'date' => now(),
            ]);
        }

        $old = $this->sale->getAttributes();
        $this->sale->update(['status' => Sale::STATUS_CANCELLED, 'cancelled_at' => now()]);
        AuditLogger::updated($this->sale, $old, 'sale.cancelled');

        session()->flash('status', 'Vente annulée, stock restitué.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.sales.sale-show');
    }
}
