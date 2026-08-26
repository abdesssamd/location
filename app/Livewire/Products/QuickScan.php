<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class QuickScan extends Component
{
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->query = trim($this->query);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $result = null;

        if (strlen($this->query) >= 2) {
            $result = Product::with('images', 'category')
                ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
                ->where(function ($q) {
                    $q->where('reference', $this->query)
                        ->orWhere('barcode', $this->query);
                })
                ->first() ?? Product::with('images', 'category')
                ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
                ->where(function ($q) {
                    $q->where('reference', 'like', '%'.$this->query.'%')
                        ->orWhere('name', 'like', '%'.$this->query.'%')
                        ->orWhere('barcode', 'like', '%'.$this->query.'%');
                })
                ->first();
        }

        $statuses = Product::statusLabels();

        return view('livewire.products.quick-scan', compact('result', 'statuses'));
    }
}