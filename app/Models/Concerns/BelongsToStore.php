<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    protected static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        // Toute donnée métier naît rattachée au magasin courant : un enregistrement
        // sans store_id échapperait au scope et deviendrait visible par tous.
        static::creating(function ($model) {
            if ($model->store_id === null) {
                $model->store_id = StoreContext::id();
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
