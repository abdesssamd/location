<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pack extends Model
{
    use BelongsToStore, HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_DRAFT = 'draft';

    public const PRICING_FIXED = 'fixed';
    public const PRICING_CALCULATED = 'calculated';

    protected $fillable = [
        'store_id',
        'category_id',
        'duplicated_from_id',
        'reference',
        'name',
        'description',
        'main_image_path',
        'pricing_mode',
        'pack_price',
        'discount_type',
        'discount_value',
        'caution',
        'status',
        'rental_conditions',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'rental_conditions' => 'array',
            'discount_value' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * withoutGlobalScopes() : une ligne de pack appartient au magasin de son
     * pack par construction — le scope tenant de PackItem est donc redondant
     * ici, et nuisible sous un contexte ambiant différent (super admin ayant
     * sélectionné un autre magasin) : il masquerait alors toutes les lignes
     * du pack, qu'elles soient valides ou non.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PackItem::class)->withoutGlobalScopes()->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PackImage::class)->withoutGlobalScopes()->orderBy('sort_order');
    }

    public function duplicatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicated_from_id');
    }

    public function primaryImage(): ?PackImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function normalPrice(): int
    {
        return (int) $this->items->sum(fn (PackItem $item) => $item->quantity * (int) $item->resolvedProduct()?->rental_price);
    }

    public function finalPrice(): int
    {
        $normal = $this->normalPrice();

        if ($this->pricing_mode === self::PRICING_FIXED) {
            return (int) $this->pack_price;
        }

        if ($this->discount_type === 'percent') {
            return max(0, (int) round($normal - ($normal * ((float) $this->discount_value / 100))));
        }

        if ($this->discount_type === 'amount') {
            return max(0, $normal - (int) round((float) $this->discount_value));
        }

        return $normal;
    }

    public function savingAmount(): int
    {
        return max(0, $this->normalPrice() - $this->finalPrice());
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'badge-green',
            self::STATUS_DRAFT => 'badge-blue',
            self::STATUS_ARCHIVED => 'badge-zinc',
            default => 'badge-zinc',
        };
    }
}
