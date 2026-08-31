<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'rental_id',
        'sale_id',
        'user_id',
        'reference',
        'amount',
        'method',
        'type',
        'date',
        'notes',
        'proof_image_paths',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'proof_image_paths' => 'array',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function methodLabels(): array
    {
        return [
            'cash' => 'Espèces',
            'card' => 'Carte',
            'transfer' => 'Virement',
            'check' => 'Chèque',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            'payment' => 'Paiement',
            'deposit' => 'Acompte',
            'refund' => 'Remboursement',
        ];
    }
}