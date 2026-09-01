<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToStore, HasFactory;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'store_id',
        'supplier_id',
        'user_id',
        'reference',
        'status',
        'subtotal',
        'total',
        'paid_amount',
        'payment_method',
        'notes',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
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
            self::STATUS_RECEIVED => 'Reçu',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_RECEIVED => 'badge-green',
            self::STATUS_CANCELLED => 'badge-red',
            default => 'badge-zinc',
        };
    }
}
