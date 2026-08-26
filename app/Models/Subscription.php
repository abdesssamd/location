<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'store_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public static function statusLabels(): array
    {
        return [
            self::STATUS_TRIAL => 'Essai',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_EXPIRED => 'Expiré',
            self::STATUS_SUSPENDED => 'Suspendu',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_PENDING => 'En attente',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE, self::STATUS_TRIAL => 'badge-green',
            self::STATUS_EXPIRED => 'badge-red',
            self::STATUS_SUSPENDED, self::STATUS_PENDING => 'badge-orange',
            self::STATUS_CANCELLED => 'badge-zinc',
            default => 'badge-zinc',
        };
    }
}