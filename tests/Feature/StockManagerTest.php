<?php

use App\Models\Product;
use App\Models\Store;
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

it('affiche l ecran de stock', function () {
    $this->actingAs($this->user)
        ->get(route('stock.index'))
        ->assertOk()
        ->assertSee('Nouveau mouvement');
});

it('enregistre une reception de stock et met a jour la quantite', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Robes', 'reference' => 'ART-000010', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 2, 'status' => 'available']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Inventory\StockManager::class)
        ->set('product_id', $product->id)
        ->set('type', 'in')
        ->set('quantity', 5)
        ->set('reason', 'Livraison fournisseur')
        ->call('addMovement');

    expect($product->refresh()->quantity)->toBe(7);
    expect(\App\Models\StockMovement::where('product_id', $product->id)->where('type', 'in')->exists())->toBeTrue();
});

it('refuse un retrait superieur au stock disponible', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Robes', 'reference' => 'ART-000011', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Inventory\StockManager::class)
        ->set('product_id', $product->id)
        ->set('type', 'out')
        ->set('quantity', 5)
        ->call('addMovement')
        ->assertSee('Stock insuffisant');

    expect($product->refresh()->quantity)->toBe(1);
    expect(\App\Models\StockMovement::where('product_id', $product->id)->count())->toBe(0);
});

it('les mouvements d un autre magasin ne sont pas visibles', function () {
    $other = Store::create(['name' => 'Autre', 'slug' => 'autre', 'token' => 'tok-other', 'status' => 'active']);
    $product = Product::create(['store_id' => $other->id, 'name' => 'Robe Cachee', 'reference' => 'R-X', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);
    StoreContext::set($other->id);
    \App\Models\StockMovement::create(['store_id' => $other->id, 'product_id' => $product->id, 'user_id' => null, 'type' => 'in', 'quantity' => 1, 'reason' => 'Secret', 'date' => now()]);

    StoreContext::set($this->store->id);

    $this->actingAs($this->user)
        ->get(route('stock.index'))
        ->assertOk()
        ->assertDontSee('Robe Cachee');
});