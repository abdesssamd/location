<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToStore, HasFactory;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'store_id',
        'customer_id',
        'user_id',
        'reference',
        'status',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'payment_method',
        'notes',
        'cancelled_at',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'badge-green',
            self::STATUS_CANCELLED => 'badge-red',
            default => 'badge-zinc',
        };
    }
}
