<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class StockManager extends Component
{
    use WithPagination;

    public ?int $product_id = null;
    public string $type = 'in';
    public int|string $quantity = 1;
    public string $reason = '';

    public string $search = '';
    public string $filterType = '';
    public string $from = '';
    public string $to = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function addMovement(): void
    {
        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:in,out,return,damage,lost,adjust'],
            'quantity' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($this->product_id);
        $this->authorize('update', $product);

        $delta = match ($this->type) {
            'in', 'return' => abs((int) $this->quantity),
            'out', 'damage', 'lost' => -abs((int) $this->quantity),
            'adjust' => (int) $this->quantity,
            default => 0,
        };

        if ($delta === 0) {
            session()->flash('error', 'La quantité d\u00e9placement doit \u00eatre non nulle.');

            return;
        }

        if ($delta < 0 && $product->quantity + $delta < 0) {
            session()->flash('error', 'Stock insuffisant ('.$product->quantity.' en stock).');

            return;
        }

        $product->increment('quantity', $delta);
        $product->status = $product->quantity > 0 ? 'available' : 'offline';
        $product->save();

        if ($delta < 0) {
            \App\Services\NotificationService::notifyLowStock($product);
        }

        StockMovement::create([
            'store_id' => $product->store_id ?? StoreContext::id(),
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $this->type,
            'quantity' => $delta,
            'reason' => $this->reason ?: null,
            'date' => now(),
        ]);

        AuditLogger::log('stock.movement', $product, null, ['type' => $this->type, 'delta' => $delta]);

        $this->reset(['product_id', 'type', 'quantity', 'reason']);
        session()->flash('status', 'Mouvement enregistré.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$this->search.'%')->orWhere('reference', 'like', '%'.$this->search.'%')))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->latest()
            ->paginate(15);

        $products = Product::query()
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->where('quantity', '>', 0)
            ->orderBy('name')
            ->get();

        $typeLabels = StockMovement::typeLabels();
        $totalStock = (int) Product::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->sum('quantity');
        $lowStockCount = Product::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->where('quantity', '>', 0)->where('quantity', '<=', 2)->count();
        $todayCount = StockMovement::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->whereDate('date', now()->toDateString())->count();
        return view('livewire.inventory.stock-manager', compact('movements', 'products', 'typeLabels', 'totalStock', 'lowStockCount', 'todayCount'));
    }
}