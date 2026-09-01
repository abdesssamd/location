<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'expense_category_id',
        'user_id',
        'reference',
        'label',
        'amount',
        'payment_method',
        'date',
        'notes',
        'proof_path',
        'is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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
}
