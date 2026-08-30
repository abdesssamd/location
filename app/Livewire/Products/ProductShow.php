<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProductShow extends Component
{
    public Product $product;

    public ?int $selectedImageId = null;

    public function mount(Product $product): void
    {
        $this->authorize('view', $product);

        // Pas de ->limit() dans l'eager load : MariaDB < 10.2 ne supporte pas les fonctions fenêtrées
        $this->product = $product->load(['images', 'category', 'stockMovements']);
        $this->selectedImageId = $this->product->primaryImage()?->id;
    }

    public function selectImage(int $imageId): void
    {
        $this->selectedImageId = $imageId;
    }

    public function changeStatus(string $status): void
    {
        $this->authorize('changeStatus', $this->product);

        if (! in_array($status, array_keys(Product::statusLabels()), true)) {
            return;
        }

        $old = $this->product->getAttributes();
        $this->product->update(['status' => $status]);
        AuditLogger::updated($this->product, $old, 'product.status_changed');
        session()->flash('status', 'Statut mis à jour.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $selectedImage = $this->product->images->firstWhere('id', $this->selectedImageId) ?? $this->product->primaryImage();

        return view('livewire.products.product-show', [
            'selectedImage' => $selectedImage,
            'statuses' => Product::statusLabels(),
        ]);
    }
}