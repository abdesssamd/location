<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'first_name',
        'last_name',
        'phone',
        'phone_secondary',
        'email',
        'cin',
        'address',
        'wilaya',
        'commune',
        'birth_date',
        'notes',
        'favorite',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'favorite' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}