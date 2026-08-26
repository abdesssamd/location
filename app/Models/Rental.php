<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
        'user_id',
        'reference',
        'start_date',
        'end_date',
        'actual_return_date',
        'status',
        'subtotal',
        'discount',
        'pack_savings',
        'caution',
        'total',
        'paid_amount',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'actual_return_date' => 'date',
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
        return $this->hasMany(RentalItem::class);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function getDaysAttribute(): int
    {
        return max(1, $this->start_date->diffInDays($this->end_date) + 1);
    }

    public function getPackSavingsAttribute($value): int
    {
        return (int) $value;
    }

    public static function statusLabels(): array
    {
        return [
            'reserved' => 'Réservée',
            'active' => 'Active',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            'reserved' => 'badge-blue',
            'active' => 'badge-green',
            'completed' => 'badge-zinc',
            'cancelled' => 'badge-red',
            default => 'badge-zinc',
        };
    }
}