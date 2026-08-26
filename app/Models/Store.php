<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'token',
        'logo_path',
        'color',
        'address',
        'wilaya',
        'commune',
        'phone',
        'phone_secondary',
        'email',
        'manager_name',
        'currency',
        'tax_rate',
        'late_fee_per_day',
        'tax_enabled',
        'rental_conditions',
        'settings',
        'contract_prefix',
        'status',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'tax_enabled' => 'boolean',
            'rental_conditions' => 'array',
            'settings' => 'array',
            'suspended_at' => 'datetime',
        ];
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function rentals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function audits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}