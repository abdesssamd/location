<?php

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    StoreContext::set($this->store->id);

    $this->customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $this->rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);
});

it('affiche la liste des contrats', function () {
    $this->actingAs($this->user)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertSee('LOC-2026-0001');
});

it('affiche le contrat d une location', function () {
    $this->actingAs($this->user)
        ->get(route('contracts.show', $this->rental))
        ->assertOk()
        ->assertSee('CONTRAT DE LOCATION')
        ->assertSee('Ali Cherif')
        ->assertSee($this->store->name);
});

it('ne montre pas les locations non eligibles (reservee, annulee)', function () {
    Rental::create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'reference' => 'LOC-2026-0002', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'reserved', 'subtotal' => 1, 'total' => 1]);

    $this->actingAs($this->user)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertDontSee('LOC-2026-0002');
});