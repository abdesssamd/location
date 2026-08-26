<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pack_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }
}
