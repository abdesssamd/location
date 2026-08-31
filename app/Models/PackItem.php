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
     * Articles candidats d'une ligne « au choix » : tous les articles de la
     * catégorie appartenant au magasin du pack.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function candidateProducts(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->category_id) {
            return Product::whereRaw('1 = 0')->get();
        }

        $storeId = $this->pack->store_id ?? \App\Services\StoreContext::id();

        return Product::where('category_id', $this->category_id)
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->whereNotIn('status', [Product::STATUS_OFFLINE, Product::STATUS_LOST])
            ->orderBy('name')
            ->get();
    }

    /**
     * Article effectivement utilisé pour cette ligne de pack.
     *
     * Produit explicite, sinon le meilleur candidat de la catégorie : celui qui
     * est réellement libre sur la période demandée. Sans les dates, on retombe
     * sur celui qui a le plus grand parc.
     */
    public function resolvedProduct(?string $startDate = null, ?string $endDate = null): ?Product
    {
        if ($this->product_id) {
            return $this->product;
        }

        $candidates = $this->candidateProducts();

        if ($candidates->isEmpty()) {
            return null;
        }

        $required = max(1, (int) $this->quantity);

        // Une ligne « au choix » ne doit pas être déclarée indisponible parce que
        // la variante tirée au sort est réservée : on prend celle qui est libre.
        if ($startDate && $endDate) {
            $availability = app(\App\Services\AvailabilityService::class);

            $free = $candidates
                ->map(fn (Product $p) => [$p, $availability->freeBetween($p, $startDate, $endDate)])
                ->sortByDesc(fn (array $pair) => $pair[1]);

            $best = $free->first();

            if ($best && $best[1] >= $required) {
                return $best[0];
            }
        }

        return $candidates->sortByDesc('quantity')->first();
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