<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    StoreContext::set($this->store->id);
});

it('cree un fournisseur', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Suppliers\SupplierManager::class)
        ->call('create')
        ->set('name', 'Textile Import')
        ->set('phone', '0550123456')
        ->call('save')
        ->assertHasNoErrors();

    $supplier = Supplier::where('store_id', $this->store->id)->where('name', 'Textile Import')->firstOrFail();
    expect($supplier->phone)->toBe('0550123456');
    expect($supplier->is_active)->toBeTrue();
});

it('modifie un fournisseur existant', function () {
    $supplier = Supplier::create(['store_id' => $this->store->id, 'name' => 'Ancien nom']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Suppliers\SupplierManager::class)
        ->call('edit', $supplier->id)
        ->set('name', 'Nouveau nom')
        ->call('save');

    expect($supplier->fresh()->name)->toBe('Nouveau nom');
});

it('enregistre un achat et augmente le stock du produit', function () {
    $supplier = Supplier::create(['store_id' => $this->store->id, 'name' => 'Fournisseur A']);
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-ACH-1', 'rental_price' => 1000, 'sale_price' => 5000, 'caution_price' => 0, 'quantity' => 3, 'status' => 'available']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Purchases\PurchaseForm::class)
        ->call('selectSupplier', $supplier->id)
        ->call('addProduct', $product->id)
        ->call('updateQuantity', 0, 5)
        ->call('updateUnitCost', 0, 2000)
        ->set('paid_amount', 5000)
        ->call('save');

    $purchase = Purchase::where('store_id', $this->store->id)->firstOrFail();
    expect($purchase->total)->toBe(10000);
    expect($purchase->paid_amount)->toBe(5000);
    expect($purchase->remaining)->toBe(5000);
    expect($purchase->reference)->toStartWith('ACH-');

    expect($product->fresh()->quantity)->toBe(8);

    expect(\App\Models\StockMovement::where('product_id', $product->id)->where('type', 'purchase')->exists())->toBeTrue();
    expect(\App\Models\Payment::where('purchase_id', $purchase->id)->sum('amount'))->toBe(5000);
});

it('annule un achat et retire le stock recu', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Chaussure', 'reference' => 'ART-ACH-2', 'rental_price' => 500, 'sale_price' => 2000, 'caution_price' => 0, 'quantity' => 10, 'status' => 'available']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Purchases\PurchaseForm::class)
        ->call('addProduct', $product->id)
        ->call('updateQuantity', 0, 4)
        ->call('updateUnitCost', 0, 1000)
        ->call('save');

    $purchase = Purchase::where('store_id', $this->store->id)->firstOrFail();
    expect($product->fresh()->quantity)->toBe(14);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Purchases\PurchaseShow::class, ['purchase' => $purchase])
        ->call('cancel');

    expect($purchase->fresh()->status)->toBe(Purchase::STATUS_CANCELLED);
    expect($product->fresh()->quantity)->toBe(10);
});

it('un employe sans permission ne peut pas ouvrir les achats', function () {
    $employe = User::create(['store_id' => $this->store->id, 'name' => 'Employe', 'email' => 'emp-ach@test.com', 'password' => 'password', 'is_active' => true]);
    $employe->assignRole('employee');

    $this->actingAs($employe)->get(route('purchases.index'))->assertForbidden();
    $this->actingAs($employe)->get(route('suppliers.index'))->assertForbidden();
});

it('un magasin ne voit pas les fournisseurs ni les achats d un autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre Ach', 'slug' => 'autre-ach', 'token' => 'tok-ach', 'status' => 'active']);
    \App\Services\SubscriptionService::createSubscription($otherStore, \App\Models\Plan::where('slug', 'pro')->firstOrFail(), \App\Models\Subscription::STATUS_ACTIVE);
    \App\Models\StoreToken::issue($otherStore->id);

    StoreContext::set($otherStore->id);
    Supplier::create(['store_id' => $otherStore->id, 'name' => 'Fournisseur Autre']);
    Purchase::create(['store_id' => $otherStore->id, 'reference' => 'ACH-AUTRE', 'status' => 'received', 'subtotal' => 1000, 'total' => 1000, 'paid_amount' => 0, 'date' => now()->toDateString()]);
    StoreContext::set($this->store->id);

    Supplier::create(['store_id' => $this->store->id, 'name' => 'Fournisseur Moi']);
    Purchase::create(['store_id' => $this->store->id, 'reference' => 'ACH-MOI', 'status' => 'received', 'subtotal' => 500, 'total' => 500, 'paid_amount' => 0, 'date' => now()->toDateString()]);

    $this->actingAs($this->user)
        ->get(route('suppliers.index'))
        ->assertOk()
        ->assertSee('Fournisseur Moi')
        ->assertDontSee('Fournisseur Autre');

    $this->actingAs($this->user)
        ->get(route('purchases.index'))
        ->assertOk()
        ->assertSee('ACH-MOI')
        ->assertDontSee('ACH-AUTRE');
});

it('le rapport deduit les achats du benefice net', function () {
    $customer = \App\Models\Customer::create(['store_id' => $this->store->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0550']);
    $rental = \App\Models\Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-ACH-1', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);
    \App\Models\Payment::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->user->id, 'reference' => 'PAY-ACH-1', 'amount' => 3000, 'method' => 'cash', 'type' => 'payment', 'date' => now()->toDateString()]);

    Purchase::create(['store_id' => $this->store->id, 'reference' => 'ACH-PROF-1', 'status' => 'received', 'subtotal' => 1500, 'total' => 1500, 'paid_amount' => 1500, 'date' => now()->toDateString()]);

    $component = Livewire::actingAs($this->user)->test(\App\Livewire\Reports\Reports::class);

    expect($component->viewData('purchaseTotal'))->toBe(1500);
    expect($component->viewData('netProfit'))->toBe(3000 - 1500);
});
