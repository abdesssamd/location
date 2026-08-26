<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public const BILLING_MONTHLY = 'monthly';
    public const BILLING_YEARLY = 'yearly';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_period',
        'max_users',
        'max_products',
        'max_customers',
        'max_storage_mb',
        'features',
        'is_active',
        'sort_order',
        'is_popular',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function months(): int
    {
        return $this->billing_period === self::BILLING_YEARLY ? 12 : 1;
    }

    public function limitLabel(?int $limit): string
    {
        return $limit === null ? 'Illimité' : (string) $limit;
    }

    public static function featureLabels(): array
    {
        return [
            'locations' => 'Gestion des locations',
            'contracts_pdf' => 'Contrats PDF',
            'qr_code' => 'QR Code',
            'statistics' => 'Statistiques',
            'advanced_statistics' => 'Statistiques avancées',
            'notifications' => 'Notifications',
            'packs' => 'Packs de location',
            'multi_users' => 'Utilisateurs multiples',
            'export_excel' => 'Export Excel/CSV',
            'api' => 'Accès API',
        ];
    }
}