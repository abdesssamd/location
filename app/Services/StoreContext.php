<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Context;

class StoreContext
{
    public static function set(?int $storeId): void
    {
        Context::add('store_id', $storeId);
        Context::add('store', $storeId ? Store::find($storeId) : null);
    }

    public static function id(): ?int
    {
        return Context::get('store_id');
    }

    public static function store(): ?Store
    {
        return Context::get('store');
    }
}