<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalItem extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'rental_id',
        'product_id',
        'pack_id',
        'pack_name',
        'quantity',
        'unit_price',
        'line_total',
        'is_pack_component',
        'return_condition',
        'return_damage_fee',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_pack_component' => 'boolean',
            'return_image_paths' => 'array',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }
}