<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    public const ACTION_CREATED = 'created';
    public const ACTION_RENEWED = 'renewed';
    public const ACTION_UPGRADED = 'upgraded';
    public const ACTION_DOWNGRADED = 'downgraded';
    public const ACTION_EXPIRED = 'expired';
    public const ACTION_SUSPENDED = 'suspended';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_PAYMENT = 'payment';

    protected $fillable = [
        'store_id',
        'old_plan_id',
        'new_plan_id',
        'action',
        'reason',
        'amount',
        'user_id',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function oldPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'old_plan_id');
    }

    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'new_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function actionLabels(): array
    {
        return [
            self::ACTION_CREATED => 'Abonnement créé',
            self::ACTION_RENEWED => 'Renouvelé',
            self::ACTION_UPGRADED => 'Changement de plan',
            self::ACTION_DOWNGRADED => 'Changement de plan',
            self::ACTION_EXPIRED => 'Expiré',
            self::ACTION_SUSPENDED => 'Suspendu',
            self::ACTION_CANCELLED => 'Annulé',
            self::ACTION_PAYMENT => 'Paiement approuvé',
        ];
    }
}