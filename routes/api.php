<?php

use App\Models\Product;
use App\Models\Rental;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.store.token')->group(function () {
    Route::get('/products', function () {
        return Product::query()
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->with('images', 'category')
            ->latest()
            ->paginate(20);
    });

    Route::get('/products/{product}', function (Product $product) {
        return $product->load('images', 'category');
    });

    Route::get('/rentals', function () {
        return Rental::query()
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->with('customer', 'items.product')
            ->latest()
            ->paginate(20);
    });

    Route::get('/rentals/{rental}', function (Rental $rental) {
        return $rental->load('customer', 'items.product');
    });
});
