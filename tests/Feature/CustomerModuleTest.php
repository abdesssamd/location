<?php

use App\Models\Customer;
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

it('affiche la liste des clients', function () {
    Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ahmed', 'last_name' => 'Benali', 'phone' => '0550 11 22 33']);

    $this->actingAs($this->user)
        ->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Ahmed Benali');
});

it('cree et modifie un client', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Customers\CustomerList::class)
        ->set('first_name', 'Sara')
        ->set('last_name', 'Haddad')
        ->set('phone', '0661 22 33 44')
        ->set('favorite', true)
        ->call('save');

    $customer = Customer::where('phone', '0661 22 33 44')->firstOrFail();
    expect($customer->full_name)->toBe('Sara Haddad');
    expect($customer->favorite)->toBeTrue();
    expect($customer->store_id)->toBe($this->store->id);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Customers\CustomerList::class)
        ->call('openEdit', $customer->id)
        ->set('first_name', 'Sara-Marie')
        ->call('save');

    expect($customer->refresh()->first_name)->toBe('Sara-Marie');
});

it('ouvre le formulaire de creation via la route create', function () {
    $this->actingAs($this->user)
        ->get(route('customers.create'))
        ->assertOk()
        ->assertSee('Nouveau client');
});

it('un magasin ne voit pas les clients d un autre magasin', function () {
    $other = Store::create(['name' => 'Autre', 'slug' => 'autre', 'token' => 'tok-other', 'status' => 'active']);
    Customer::create(['store_id' => $other->id, 'first_name' => 'Lina', 'last_name' => 'Secret', 'phone' => '0000 00 00 00']);

    $this->actingAs($this->user)
        ->get(route('customers.index'))
        ->assertOk()
        ->assertDontSee('Lina Secret');
});

it('supprime un client', function () {
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Karim', 'last_name' => 'Slimani', 'phone' => '0777 00 00 00']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Customers\CustomerList::class)
        ->call('deleteCustomer', $customer->id);

    expect(Customer::find($customer->id))->toBeNull();
});