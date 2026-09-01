<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PackReturnController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->is_super_admin
            ? redirect()->route('admin.index')
            : redirect()->route('dashboard');
    }

    return view('landing', [
        'plans' => \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get(),
        'trialDays' => \App\Models\PlatformSetting::trialDays(),
        'signupEnabled' => \App\Models\PlatformSetting::signupEnabled(),
    ]);
})->name('home');

Route::get('locale/{locale}', \App\Http\Controllers\LocaleController::class)
    ->middleware('auth')
    ->name('locale.switch');

Route::middleware(['auth'])->group(function () {
    // Le super admin choisit le magasin sur lequel il travaille dans l'espace magasin.
    Route::post('store-context', function (\Illuminate\Http\Request $request) {
        abort_unless($request->user()->is_super_admin, 403);

        $storeId = (int) $request->input('store_id');
        abort_unless(\App\Models\Store::whereKey($storeId)->exists(), 404);

        $request->session()->put('admin_store_id', $storeId);

        return back()->with('status', 'Magasin courant : '.\App\Models\Store::whereKey($storeId)->value('name'));
    })->name('store.context.switch');

    Route::get('dashboard', \App\Livewire\Dashboard::class)
        ->middleware('store.context')
        ->name('dashboard');

    // --- Abonnement SaaS ---
    Route::get('subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('subscription.plans');
    Route::post('subscription/subscribe/{plan}', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('subscription/pay', [\App\Http\Controllers\SubscriptionController::class, 'payOffline'])->name('subscription.pay');

    Route::get('team', \App\Livewire\Team\ManageUsers::class)
        ->middleware(['store.context', 'permission:users.manage'])
        ->name('team.index');

    Route::get('settings', \App\Livewire\Settings\StoreSettings::class)
        ->middleware(['store.context', 'permission:settings.manage'])
        ->name('settings.index');

    Route::get('settings/profile', function () {
        return \Illuminate\Support\Facades\Redirect::route('settings.profile', absolute: false);
    })->name('settings');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // --- Articles / Catégories ---
    Route::get('products', \App\Livewire\Products\ProductList::class)
        ->middleware(['store.context', 'permission:products.view'])
        ->name('products.index');

    Route::get('products/create', \App\Livewire\Products\ProductForm::class)
        ->middleware(['store.context', 'permission:products.create'])
        ->name('products.create');

    Route::get('products/{product}', \App\Livewire\Products\ProductShow::class)
        ->middleware(['store.context', 'permission:products.view'])
        ->name('products.show');

    Route::get('products/{product}/edit', \App\Livewire\Products\ProductForm::class)
        ->middleware(['store.context', 'permission:products.edit'])
        ->name('products.edit');

    Route::get('scan/{product}', [ProductController::class, 'scan'])
        ->middleware(['store.context', 'permission:products.view'])
        ->name('products.scan');

    Route::get('scan', \App\Livewire\Products\QuickScan::class)
        ->middleware(['store.context', 'permission:products.view'])
        ->name('scan.index');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware(['store.context', 'permission:products.delete'])
        ->name('products.destroy');

    Route::get('categories', \App\Livewire\Categories\CategoryManager::class)
        ->middleware(['store.context', 'permission:categories.manage'])
        ->name('categories.index');

    // --- Packs ---
    Route::get('packs', \App\Livewire\Packs\PackList::class)
        ->middleware(['store.context', 'permission:packs.view'])
        ->name('packs.index');

    Route::get('packs/create', \App\Livewire\Packs\PackForm::class)
        ->middleware(['store.context', 'permission:packs.create'])
        ->name('packs.create');

    Route::get('packs/{pack}', \App\Livewire\Packs\PackShow::class)
        ->middleware(['store.context', 'permission:packs.view'])
        ->name('packs.show');

    Route::get('packs/{pack}/edit', \App\Livewire\Packs\PackForm::class)
        ->middleware(['store.context', 'permission:packs.edit'])
        ->name('packs.edit');

    Route::get('stock', \App\Livewire\Inventory\StockManager::class)
        ->middleware(['store.context', 'permission:stock.manage'])
        ->name('stock.index');

    // --- Clients ---
    Route::get('customers', \App\Livewire\Customers\CustomerList::class)
        ->middleware(['store.context', 'permission:customers.view'])
        ->name('customers.index');

    Route::get('customers/create', \App\Livewire\Customers\CustomerList::class)
        ->middleware(['store.context', 'permission:customers.create'])
        ->name('customers.create');

    Route::get('customers/{customer}', \App\Livewire\Customers\CustomerShow::class)
        ->middleware(['store.context', 'permission:customers.view'])
        ->name('customers.show');

    Route::get('customers/{customer}/edit', \App\Livewire\Customers\CustomerList::class)
        ->middleware(['store.context', 'permission:customers.edit'])
        ->name('customers.edit');

    // --- Réservations & Locations ---
    Route::get('rentals', \App\Livewire\Rentals\RentalList::class)
        ->middleware(['store.context', 'permission:rentals.view'])
        ->name('rentals.index');

    Route::get('rentals/create', \App\Livewire\Rentals\RentalForm::class)
        ->middleware(['store.context', 'permission:rentals.create'])
        ->name('rentals.create');

    Route::get('rentals/{rental}', \App\Livewire\Rentals\RentalShow::class)
        ->middleware(['store.context', 'permission:rentals.view'])
        ->name('rentals.show');

    Route::get('rentals/{rental}/edit', \App\Livewire\Rentals\RentalForm::class)
        ->middleware(['store.context', 'permission:rentals.create'])
        ->name('rentals.edit');

    Route::get('calendar', \App\Livewire\Calendar::class)
        ->middleware(['store.context', 'permission:rentals.view'])
        ->name('calendar');

    // --- Gestion commerciale (vente, dépenses, fournisseurs/achats) ---
    // Regroupées sous plan.feature:commercial : un magasin dont le plan n'inclut
    // pas ce module n'y accède pas, même avec les permissions de rôle adéquates.
    Route::middleware(['plan.feature:commercial'])->group(function () {
        // --- Ventes ---
        Route::get('sales', \App\Livewire\Sales\SaleList::class)
            ->middleware(['store.context', 'permission:sales.view'])
            ->name('sales.index');

        Route::get('sales/create', \App\Livewire\Sales\SalePos::class)
            ->middleware(['store.context', 'permission:sales.create'])
            ->name('sales.create');

        Route::get('sales/{sale}', \App\Livewire\Sales\SaleShow::class)
            ->middleware(['store.context', 'permission:sales.view'])
            ->name('sales.show');

        // --- Dépenses ---
        Route::get('expenses', \App\Livewire\Expenses\ExpenseManager::class)
            ->middleware(['store.context', 'permission:expenses.view'])
            ->name('expenses.index');

        // --- Fournisseurs & achats ---
        Route::get('suppliers', \App\Livewire\Suppliers\SupplierManager::class)
            ->middleware(['store.context', 'permission:suppliers.view'])
            ->name('suppliers.index');

        Route::get('purchases', \App\Livewire\Purchases\PurchaseList::class)
            ->middleware(['store.context', 'permission:purchases.view'])
            ->name('purchases.index');

        Route::get('purchases/create', \App\Livewire\Purchases\PurchaseForm::class)
            ->middleware(['store.context', 'permission:purchases.create'])
            ->name('purchases.create');

        Route::get('purchases/{purchase}', \App\Livewire\Purchases\PurchaseShow::class)
            ->middleware(['store.context', 'permission:purchases.view'])
            ->name('purchases.show');
    });

    // --- Paiements ---
    Route::get('payments', \App\Livewire\Payments\PaymentManager::class)
        ->middleware(['store.context', 'permission:payments.view'])
        ->name('payments.index');

    // --- Contrats ---
    Route::get('contracts', \App\Livewire\Contracts\ContractList::class)
        ->middleware(['store.context', 'permission:contracts.view'])
        ->name('contracts.index');

    Route::get('contracts/{rental}/view', [ContractController::class, 'show'])
        ->middleware(['store.context', 'permission:contracts.view'])
        ->name('contracts.show');

    Route::get('contracts/{rental}/pdf', [ContractController::class, 'pdf'])
        ->middleware(['store.context', 'permission:contracts.pdf'])
        ->name('contracts.pdf');

    Route::get('contracts/{rental}/pack-return/view', [PackReturnController::class, 'show'])
        ->middleware(['store.context', 'permission:contracts.view'])
        ->name('contracts.pack-return.show');

    Route::get('contracts/{rental}/pack-return/pdf', [PackReturnController::class, 'pdf'])
        ->middleware(['store.context', 'permission:contracts.pdf'])
        ->name('contracts.pack-return.pdf');

    // --- Fichiers sensibles (jamais servis directement par /storage) ---
    Route::get('files/payments/{payment}/{index}', [\App\Http\Controllers\SecureFileController::class, 'payment'])
        ->middleware(['store.context', 'permission:payments.view'])
        ->whereNumber('index')
        ->name('files.payment');

    Route::get('files/returns/{item}/{index}', [\App\Http\Controllers\SecureFileController::class, 'rentalReturn'])
        ->middleware(['store.context', 'permission:rentals.view'])
        ->whereNumber('index')
        ->name('files.return');

    Route::get('files/subscription-proofs/{payment}', [\App\Http\Controllers\SecureFileController::class, 'subscriptionProof'])
        ->middleware('store.context')
        ->name('files.subscription-proof');

    Route::get('files/expenses/{expense}', [\App\Http\Controllers\SecureFileController::class, 'expense'])
        ->middleware(['store.context', 'permission:expenses.view'])
        ->name('files.expense');

    // --- Rapports ---
    Route::get('reports', \App\Livewire\Reports\Reports::class)
        ->middleware(['store.context', 'permission:reports.view'])
        ->name('reports.index');
});

// Espace Super Admin (accès global, sans scope tenant)
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.index')->name('index');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('stores/create', [StoreController::class, 'create'])->name('stores.create');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');
    Route::get('stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
    Route::put('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::post('stores/{store}/toggle-status', [StoreController::class, 'toggleStatus'])->name('stores.toggle-status');
    Route::post('stores/{store}/admins', [StoreController::class, 'createAdmin'])->name('stores.admins.store');
    Route::post('stores/{store}/admins/{admin}/reset-password', [StoreController::class, 'resetAdminPassword'])->name('stores.admins.reset-password');
    Route::delete('stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');

    Route::post('stores/{store}/approve', [StoreController::class, 'approve'])->name('stores.approve');

    Route::get('settings', \App\Livewire\Admin\PlatformSettingsManager::class)->name('settings');

    Route::get('audits', [AdminAuditController::class, 'index'])->name('audits.index');
    Route::get('stores/{store}/export', [AdminAuditController::class, 'exportStore'])->name('stores.export');

    // --- Abonnements SaaS ---
    Route::get('plans', \App\Livewire\Admin\PlanManager::class)->name('plans.index');
    Route::get('subscriptions', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions/{payment}/approve', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'approve'])->name('subscriptions.approve');
    Route::post('subscriptions/{payment}/reject', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'reject'])->name('subscriptions.reject');
    Route::post('stores/{store}/renew', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'renew'])->name('stores.renew');
    Route::post('stores/{store}/change-plan', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'changePlan'])->name('stores.change-plan');
    Route::post('stores/{store}/tokens/regenerate', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'regenerateToken'])->name('stores.tokens.regenerate');
    Route::post('stores/{store}/tokens/revoke', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'revokeToken'])->name('stores.tokens.revoke');
});

require __DIR__.'/auth.php';