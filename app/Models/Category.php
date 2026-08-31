<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'parent_id',
        'name',
        'icon',
        'color',
        'sizes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sizes' => 'array',
        ];
    }

    /**
     * Tailles disponibles pour cette catégorie ; hérite de la catégorie parente
     * si elle n'en définit pas elle-même (ex. une sous-catégorie « Costumes
     * homme » sans tailles propres utilise celles de « Costumes »).
     *
     * @return array<int, string>
     */
    public function effectiveSizes(): array
    {
        if (! empty($this->sizes)) {
            return $this->sizes;
        }

        return $this->parent?->effectiveSizes() ?? [];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class);
    }
}