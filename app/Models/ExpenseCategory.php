<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'color',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return array<int, string> */
    public static function defaultNames(): array
    {
        return [
            'Loyer',
            'Électricité / Eau',
            'Salaires',
            'Réparation / Maintenance',
            'Fournitures',
            'Transport',
            'Marketing',
            'Autre',
        ];
    }
}
