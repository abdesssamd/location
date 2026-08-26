<?php

use App\Models\Customer;
use App\Models\Payment;
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
    $this->rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000, 'paid_amount' => 0]);
});

it('affiche l ecran des paiements', function () {
    $this->actingAs($this->user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertSee('Nouveau paiement');
});

it('enregistre un paiement et maj le montant paye', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Payments\PaymentManager::class)
        ->set('rental_id', $this->rental->id)
        ->set('amount', 1000)
        ->set('method', 'cash')
        ->set('type', 'payment')
        ->set('date', now()->toDateString())
        ->call('recordPayment');

    expect($this->rental->refresh()->paid_amount)->toBe(1000);
    $payment = Payment::firstOrFail();
    expect($payment->reference)->toStartWith('PAY-');
    expect($payment->rental_id)->toBe($this->rental->id);
});

it('refuse un paiement depassant le total', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Payments\PaymentManager::class)
        ->set('rental_id', $this->rental->id)
        ->set('amount', 5000)
        ->set('method', 'cash')
        ->set('type', 'payment')
        ->set('date', now()->toDateString())
        ->call('recordPayment')
        ->assertSee('dépasse le montant de la location');

    expect($this->rental->refresh()->paid_amount)->toBe(0);
});

it('refuse un remboursement superieur au paye', function () {
    $this->rental->update(['paid_amount' => 1000]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Payments\PaymentManager::class)
        ->set('rental_id', $this->rental->id)
        ->set('amount', 2000)
        ->set('method', 'cash')
        ->set('type', 'refund')
        ->set('date', now()->toDateString())
        ->call('recordPayment')
        ->assertSee('dépasse le montant déjà payé');
});

it('les paiements d un autre magasin ne sont pas visibles', function () {
    $other = Store::create(['name' => 'Autre', 'slug' => 'autre', 'token' => 'tok-other', 'status' => 'active']);
    $otherCustomer = Customer::create(['store_id' => $other->id, 'first_name' => 'Zineb', 'last_name' => 'Secret', 'phone' => '0000 00 00 00']);
    $otherRental = Rental::create(['store_id' => $other->id, 'customer_id' => $otherCustomer->id, 'reference' => 'LOC-9999-9999', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 1, 'total' => 1]);
    Payment::create(['store_id' => $other->id, 'rental_id' => $otherRental->id, 'reference' => 'PAY-9999-9999', 'amount' => 1, 'method' => 'cash', 'type' => 'payment', 'date' => now()]);

    $this->actingAs($this->user)
        ->get(route('payments.index'))
        ->assertOk()
        ->assertDontSee('PAY-9999-9999');
});