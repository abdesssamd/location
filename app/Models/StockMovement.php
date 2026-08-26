<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'reason',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function typeLabels(): array
    {
        return [
            'in' => 'Réception',
            'out' => 'Retrait',
            'return' => 'Retour location',
            'damage' => 'Nettoyage / Endommagé',
            'lost' => 'Perdu',
            'adjust' => 'Ajustement',
        ];
    }

    public static function typeBadge(string $type): string
    {
        return match ($type) {
            'in' => 'badge-green',
            'return' => 'badge-blue',
            'out' => 'badge-orange',
            'damage' => 'badge-yellow',
            'lost' => 'badge-red',
            'adjust' => 'badge-zinc',
            default => 'badge-zinc',
        };
    }
}