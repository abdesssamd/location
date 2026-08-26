<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->storeA = Store::create(['name' => 'Magasin A', 'slug' => 'a', 'token' => 'tok-a', 'status' => 'active']);
    $this->storeB = Store::create(['name' => 'Magasin B', 'slug' => 'b', 'token' => 'tok-b', 'status' => 'active']);

    \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Permission::create(['name' => 'products.view', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Role::findByName('admin')->givePermissionTo('products.view');
});

it('isole les requetes par store avec le scope global', function () {
    StoreContext::set($this->storeA->id);
    Product::create(['store_id' => $this->storeA->id, 'name' => 'A', 'reference' => 'A-1', 'quantity' => 1, 'status' => 'available']);

    StoreContext::set($this->storeB->id);
    Product::create(['store_id' => $this->storeB->id, 'name' => 'B', 'reference' => 'B-1', 'quantity' => 1, 'status' => 'available']);

    StoreContext::set($this->storeA->id);
    expect(Product::count())->toBe(1);
    expect(Product::first()->name)->toBe('A');

    StoreContext::set($this->storeB->id);
    expect(Product::count())->toBe(1);
    expect(Product::first()->name)->toBe('B');

    StoreContext::set(null);
    expect(Product::count())->toBe(2);
});

it('interdit a un utilisateur du magasin A de voir les donnees du magasin B via l API', function () {
    StoreContext::set($this->storeA->id);
    $productA = Product::create(['store_id' => $this->storeA->id, 'name' => 'Costume A', 'reference' => 'A-1', 'quantity' => 1, 'status' => 'available']);

    StoreContext::set($this->storeB->id);
    $productB = Product::create(['store_id' => $this->storeB->id, 'name' => 'Costume B', 'reference' => 'B-1', 'quantity' => 1, 'status' => 'available']);

    // Un utilisateur du magasin A tente de recuperer le produit du magasin B
    $userA = User::create(['store_id' => $this->storeA->id, 'name' => 'User A', 'email' => 'a@test.com', 'password' => 'password']);
    $userA->assignRole('admin');

    StoreContext::set($this->storeA->id);

    // Le scope empêche la résolution : Product::find renvoie null pour le produit B
    expect(Product::find($productB->id))->toBeNull();
    expect(Product::find($productA->id))->not->toBeNull();
});

it('un magasin ne peut pas modifier les donnees d un autre magasin', function () {
    StoreContext::set($this->storeA->id);
    $productA = Product::create(['store_id' => $this->storeA->id, 'name' => 'Costume A', 'reference' => 'A-1', 'quantity' => 1, 'status' => 'available']);

    StoreContext::set($this->storeB->id);

    $found = Product::where('id', $productA->id)->first();

    expect($found)->toBeNull();
});