<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackItem extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'pack_id',
        'product_id',
        'category_id',
        'quantity',
        'selection_mode',
        'variant_hint',
        'sort_order',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isCategoryBased(): bool
    {
        return $this->category_id !== null && $this->product_id === null;
    }

    /**
     * Article effectivement utilisé pour cette ligne de pack.
     * Produit explicite, sinon premier article disponible de la catégorie.
     */
    public function resolvedProduct(): ?Product
    {
        if ($this->product_id) {
            return $this->product;
        }

        if ($this->category_id) {
            $storeId = $this->pack->store_id ?? \App\Services\StoreContext::id();

            return Product::where('category_id', $this->category_id)
                ->where('store_id', $storeId)
                ->where('status', '!=', Product::STATUS_OFFLINE)
                ->where('quantity', '>', 0)
                ->orderByDesc('quantity')
                ->first()
                ?? Product::where('category_id', $this->category_id)
                    ->where('store_id', $storeId)
                    ->orderBy('name')
                    ->first();
        }

        return null;
    }

    /**
     * Libellé affiché : article précis ou « Catégorie (au choix) ».
     */
    public function displayLabel(): string
    {
        if ($this->product_id) {
            return $this->product?->name ?? 'Article supprimé';
        }

        if ($this->category_id) {
            return ($this->category?->name ?? 'Catégorie').' (au choix)';
        }

        return 'Article supprimé';
    }
}