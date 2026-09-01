<?php

namespace App\Livewire\Purchases;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PurchaseShow extends Component
{
    public Purchase $purchase;

    public function mount(Purchase $purchase): void
    {
        $this->authorize('view', $purchase);
        $this->purchase = $purchase->load(['supplier', 'user', 'items.product', 'payments']);
    }

    /**
     * Annule l'achat et retire du stock ce qui avait été reçu : le mouvement
     * inverse de la réception, comme l'annulation d'une vente restitue le stock.
     */
    public function cancel(): void
    {
        $this->authorize('cancel', $this->purchase);

        if ($this->purchase->status !== Purchase::STATUS_RECEIVED) {
            session()->flash('error', 'Cet achat est déjà annulé.');

            return;
        }

        foreach ($this->purchase->items as $item) {
            $product = Product::find($item->product_id);

            if (! $product) {
                continue;
            }

            $product->decrement('quantity', min($item->quantity, $product->quantity));

            StockMovement::create([
                'store_id' => $product->store_id ?? StoreContext::id(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'out',
                'quantity' => -$item->quantity,
                'reason' => 'Annulation achat '.$this->purchase->reference,
                'date' => now(),
            ]);
        }

        $old = $this->purchase->getAttributes();
        $this->purchase->update(['status' => Purchase::STATUS_CANCELLED]);
        AuditLogger::updated($this->purchase, $old, 'purchase.cancelled');

        session()->flash('status', 'Achat annulé, stock retiré.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.purchases.purchase-show');
    }
}
