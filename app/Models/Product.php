<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToStore, HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_RENTED = 'rented';
    public const STATUS_RETURNING = 'returning';
    public const STATUS_CLEANING = 'cleaning';
    public const STATUS_REPAIR = 'repair';
    public const STATUS_LOST = 'lost';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_OFFLINE = 'offline';

    protected $fillable = [
        'store_id',
        'category_id',
        'reference',
        'name',
        'description',
        'brand',
        'size',
        'color',
        'material',
        'rental_price',
        'caution_price',
        'sale_price',
        'quantity',
        'status',
        'barcode',
        'qr_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rental_price' => 'decimal:2',
            'caution_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function freeBetween(string $start, string $end, ?int $ignoreRentalId = null): int
    {
        return app(\App\Services\AvailabilityService::class)->freeBetween($this, $start, $end, $ignoreRentalId);
    }

    public function freeNow(): int
    {
        return $this->freeBetween(now()->toDateString(), now()->addYears(10)->toDateString());
    }

    public function generateQrCodeValue(): string
    {
        return route('products.scan', $this, absolute: false);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Disponible',
            self::STATUS_RESERVED => 'Réservé',
            self::STATUS_RENTED => 'Loué',
            self::STATUS_RETURNING => 'En retour',
            self::STATUS_CLEANING => 'En nettoyage',
            self::STATUS_REPAIR => 'En réparation',
            self::STATUS_LOST => 'Perdu',
            self::STATUS_DAMAGED => 'Endommagé',
            self::STATUS_OFFLINE => 'Hors service',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => 'badge-green',
            self::STATUS_RESERVED => 'badge-blue',
            self::STATUS_RENTED => 'badge-orange',
            self::STATUS_RETURNING => 'badge-violet',
            self::STATUS_CLEANING => 'badge-yellow',
            self::STATUS_REPAIR => 'badge-blue',
            self::STATUS_LOST => 'badge-red',
            self::STATUS_DAMAGED => 'badge-red',
            default => 'badge-zinc',
        };
    }
}