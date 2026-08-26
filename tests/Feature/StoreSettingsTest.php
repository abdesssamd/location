<?php

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

it('affiche la page parametres du magasin', function () {
    $this->actingAs($this->user)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Paramètres du magasin');
});

it('met a jour les parametres financiers du magasin', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Settings\StoreSettings::class)
        ->set('currency', 'EUR')
        ->set('tax_enabled', true)
        ->set('tax_rate', '19')
        ->set('rental_conditions', "Condition 1\nCondition 2")
        ->call('saveFinancial');

    expect($this->store->refresh()->currency)->toBe('EUR');
    expect($this->store->tax_enabled)->toBeTrue();
    expect((float) $this->store->tax_rate)->toBe(19.0);
    expect($this->store->rental_conditions)->toBe(['Condition 1', 'Condition 2']);
});

it('met a jour la couleur de la marque du magasin', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Settings\StoreSettings::class)
        ->set('color', '#7c3aed')
        ->call('saveGeneral');

    expect($this->store->refresh()->color)->toBe('#7c3aed');
});

it('refuse une couleur de marque invalide', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Settings\StoreSettings::class)
        ->set('color', 'violet')
        ->call('saveGeneral')
        ->assertHasErrors('color');
});

it('cree un employe avec un role', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Team\ManageUsers::class)
        ->set('name', 'Caissier Test')
        ->set('email', 'caissier@test.com')
        ->set('password', 'secret123')
        ->set('role', 'cashier')
        ->call('save');

    $created = User::where('email', 'caissier@test.com')->firstOrFail();
    expect($created->hasRole('cashier'))->toBeTrue();
});