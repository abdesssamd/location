<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ProductList extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $categoryId = null;
    public string $status = '';
    public string $color = '';
    public string $size = '';
    public string $viewMode = 'cards';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);
        AuditLogger::deleted($product, 'product.deleted');
        $product->delete();
        session()->flash('status', 'Article supprimé.');
    }

    public function duplicateProduct(int $productId): void
    {
        $original = Product::with('images')->findOrFail($productId);
        $copy = $original->replicate();
        $copy->name = $original->name.' (copie)';
        $copy->reference = $this->suggestReference();
        $copy->status = 'available';
        $copy->save();

        AuditLogger::created($copy, 'product.duplicated');

        session()->flash('status', 'Article dupliqué.');
        $this->redirect(route('products.edit', $copy), navigate: true);
    }

    protected function suggestReference(): string
    {
        $next = (int) Product::query()->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))->count() + 1;

        return sprintf('ART-%06d', $next);
    }

    public function recordStockMovement(int $productId, string $type): void
    {
        $product = Product::findOrFail($productId);

        $delta = match ($type) {
            'in' => +1,
            'out' => -1,
            default => 0,
        };

        if ($delta < 0 && $product->quantity < 1) {
            session()->flash('error', 'Stock insuffisant.');

            return;
        }

        $product->increment('quantity', $delta);
        $product->status = $product->quantity > 0 ? 'available' : 'offline';
        $product->save();

        if ($delta < 0) {
            \App\Services\NotificationService::notifyLowStock($product);
        }

        StockMovement::create([
            'store_id' => StoreContext::id(),
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity' => $delta,
            'reason' => 'Manuel',
            'date' => now(),
        ]);

        AuditLogger::log('stock.movement', $product, null, ['type' => $type, 'delta' => $delta]);
        session()->flash('status', 'Stock mis à jour.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $products = Product::query()
            ->with(['category', 'images'])
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('reference', 'like', '%'.$this->search.'%')
                    ->orWhere('barcode', 'like', '%'.$this->search.'%')
                    ->orWhere('color', 'like', '%'.$this->search.'%');
            }))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->color, fn ($q) => $q->where('color', $this->color))
            ->when($this->size, fn ($q) => $q->where('size', $this->size))
            ->latest()
            ->paginate(12);

        $categories = Category::orderBy('name')->get();
        $statuses = Product::statusLabels();

        return view('livewire.products.product-list', compact('products', 'categories', 'statuses'));
    }
}