<?php

use App\Models\Product;
use App\Models\Rental;
use Illuminate\Support\Facades\Route;

/*
| Le middleware ApiTokenAuth est appliqué au groupe « api » dans bootstrap/app.php,
| en amont de SubstituteBindings : le contexte magasin est donc déjà posé quand les
| modèles sont résolus, et le scope global filtre les routes {product} / {rental}.
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/products', function () {
        return Product::query()
            ->with('images', 'category')
            ->latest()
            ->paginate(20);
    });

    Route::get('/products/{product}', function (Product $product) {
        return $product->load('images', 'category');
    });

    Route::get('/rentals', function () {
        return Rental::query()
            ->with('customer', 'items.product')
            ->latest()
            ->paginate(20);
    });

    Route::get('/rentals/{rental}', function (Rental $rental) {
        return $rental->load('customer', 'items.product');
    });
});
