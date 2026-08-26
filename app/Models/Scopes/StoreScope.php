<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = Context::get('store_id');

        if ($storeId !== null) {
            $builder->where($model->qualifyColumn('store_id'), $storeId);
        }
    }
}