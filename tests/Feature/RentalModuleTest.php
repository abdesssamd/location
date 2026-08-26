<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
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

    $this->customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $this->product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume Bleu', 'reference' => 'ART-000020', 'rental_price' => 3000, 'caution_price' => 6000, 'quantity' => 5, 'status' => 'available']);
});

it('affiche la liste des locations', function () {
    $this->actingAs($this->user)
        ->get(route('rentals.index'))
        ->assertOk();
});

it('cree une reservation et deduit le stock', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(3)->toDateString())
        ->set('items', [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 3000]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->firstOrFail();
    expect($rental->status)->toBe('reserved');
    expect($rental->subtotal)->toBe(6000);
    expect($rental->total)->toBe(6000);
    expect($rental->reference)->toStartWith('LOC-');
    expect($this->product->refresh()->quantity)->toBe(3);
    expect(\App\Models\StockMovement::where('product_id', $this->product->id)->where('type', 'out')->exists())->toBeTrue();
});

it('demarre puis termine une location en restituant le stock', function () {
    $rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'reserved', 'subtotal' => 3000, 'total' => 3000]);
    \App\Models\RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000]);
    $this->product->decrement('quantity', 1);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalShow::class, ['rental' => $rental])
        ->call('checkout');

    expect($rental->refresh()->status)->toBe('active');

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalShow::class, ['rental' => $rental])
        ->call('complete');

    expect($rental->refresh()->status)->toBe('completed');
    expect($rental->refresh()->actual_return_date)->not->toBeNull();
    expect($this->product->refresh()->quantity)->toBe(5);
});

it('annule une reservation et restitue le stock', function () {
    $rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0002', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'reserved', 'subtotal' => 3000, 'total' => 3000]);
    \App\Models\RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000]);
    $this->product->decrement('quantity', 1);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalShow::class, ['rental' => $rental])
        ->call('cancel');

    expect($rental->refresh()->status)->toBe('cancelled');
    expect($this->product->refresh()->quantity)->toBe(5);
});

it('refuse de creer une reservation sans client ni article', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(3)->toDateString())
        ->call('save')
        ->assertHasErrors(['customer_id', 'items']);
});

it('les locations d un autre magasin ne sont pas visibles', function () {
    $other = Store::create(['name' => 'Autre', 'slug' => 'autre', 'token' => 'tok-other', 'status' => 'active']);
    $otherCustomer = Customer::create(['store_id' => $other->id, 'first_name' => 'Zineb', 'last_name' => 'Secret', 'phone' => '0000 00 00 00']);
    Rental::create(['store_id' => $other->id, 'customer_id' => $otherCustomer->id, 'reference' => 'LOC-9999-9999', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'reserved', 'subtotal' => 1, 'total' => 1]);

    $this->actingAs($this->user)
        ->get(route('rentals.index'))
        ->assertOk()
        ->assertDontSee('LOC-9999-9999');
});