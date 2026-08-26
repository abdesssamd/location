<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const METHODS = [
        'cash' => 'Espèces',
        'bank_transfer' => 'Virement bancaire',
        'ccp' => 'CCP',
        'edahabia' => 'Edahabia',
        'baridimob' => 'BaridiMob',
        'online' => 'Paiement en ligne',
    ];

    protected $fillable = [
        'store_id',
        'plan_id',
        'amount',
        'method',
        'status',
        'reference',
        'proof_path',
        'notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Refusé',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'badge-green',
            self::STATUS_REJECTED => 'badge-red',
            default => 'badge-orange',
        };
    }
}