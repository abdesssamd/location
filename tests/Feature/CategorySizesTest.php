<?php

use App\Models\Category;
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

it('enregistre les tailles d une categorie depuis une saisie separee par des virgules', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Categories\CategoryManager::class)
        ->set('name', 'Costumes')
        ->set('sizesInput', '40, 42, 50, 58')
        ->call('save');

    $category = Category::where('name', 'Costumes')->firstOrFail();
    expect($category->sizes)->toBe(['40', '42', '50', '58']);
});

it('modifie les tailles d une categorie existante', function () {
    $category = Category::create(['store_id' => $this->store->id, 'name' => 'Chaussures', 'sizes' => ['36', '37']]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Categories\CategoryManager::class)
        ->call('edit', $category->id)
        ->assertSet('sizesInput', '36, 37')
        ->set('sizesInput', '36, 37, 38, 39')
        ->call('save');

    expect($category->refresh()->sizes)->toBe(['36', '37', '38', '39']);
});

it('une sous-categorie herite des tailles de son parent si elle n en definit pas', function () {
    $parent = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes', 'sizes' => ['40', '42', '50']]);
    $child = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes homme', 'parent_id' => $parent->id]);

    expect($child->effectiveSizes())->toBe(['40', '42', '50']);
});

it('une sous-categorie avec ses propres tailles ne remonte pas au parent', function () {
    $parent = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes', 'sizes' => ['40', '42', '50']]);
    $child = Category::create(['store_id' => $this->store->id, 'name' => 'Costumes enfant', 'parent_id' => $parent->id, 'sizes' => ['4 ans', '6 ans']]);

    expect($child->effectiveSizes())->toBe(['4 ans', '6 ans']);
});

it('une categorie sans tailles ni parent n a aucune taille effective', function () {
    $category = Category::create(['store_id' => $this->store->id, 'name' => 'Décoration']);

    expect($category->effectiveSizes())->toBe([]);
});
