<?php

use App\Models\Category;
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
    StoreContext::set($this->store->id);

    $this->employe = User::create([
        'store_id' => $this->store->id, 'name' => 'Employe', 'email' => 'employe-catalogue@test.com',
        'password' => 'password', 'is_active' => true,
    ]);
    $this->employe->assignRole('employee');
});

it('un employe peut ouvrir le formulaire de creation d article', function () {
    $this->actingAs($this->employe)->get(route('products.create'))->assertOk();
});

it('un employe peut ouvrir le formulaire de creation de pack', function () {
    $this->actingAs($this->employe)->get(route('packs.create'))->assertOk();
});

it('un employe peut ouvrir les categories', function () {
    // Creer un article impose d'en choisir la categorie.
    $this->actingAs($this->employe)->get(route('categories.index'))->assertOk();
});

it('un employe cree reellement un article', function () {
    $category = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes EMP']);

    Livewire::actingAs($this->employe)
        ->test(\App\Livewire\Products\ProductForm::class)
        ->set('name', 'Costume employe')
        ->set('category_id', $category->id)
        ->set('rental_price', 2000)
        ->set('quantity', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::where('store_id', $this->store->id)->where('name', 'Costume employe')->exists())->toBeTrue();
});

it('un employe peut modifier un article existant', function () {
    $product = Product::create([
        'store_id' => $this->store->id, 'name' => 'Ancien nom', 'reference' => 'ART-EMP-1',
        'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 1, 'status' => 'available',
    ]);

    $this->actingAs($this->employe)->get(route('products.edit', $product))->assertOk();
});

it('un employe ne peut toujours pas supprimer un article ni gerer le stock', function () {
    // Alimenter le catalogue ne donne pas le droit d'effacer ni de corriger les
    // quantites : ces actions restent reservees aux responsables.
    expect($this->employe->can('products.delete'))->toBeFalse();
    expect($this->employe->can('stock.manage'))->toBeFalse();
    expect($this->employe->can('packs.archive'))->toBeFalse();

    $this->actingAs($this->employe)->get(route('stock.index'))->assertForbidden();
});

it('un employe n a toujours pas acces aux donnees financieres', function () {
    expect($this->employe->can('finance.view'))->toBeFalse();
    expect($this->employe->can('payments.view'))->toBeFalse();

    $this->actingAs($this->employe)->get(route('payments.index'))->assertForbidden();
});
