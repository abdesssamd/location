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
    StoreContext::set($this->store->id);

    $this->employe = User::create([
        'store_id' => $this->store->id, 'name' => 'Employe', 'email' => 'employe-finance@test.com',
        'password' => 'password', 'is_active' => true,
    ]);
    $this->employe->assignRole('employee');

    // Un encaissement existant : il ne doit apparaitre nulle part pour l'employe.
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0550']);
    $rental = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->employe->id,
        'reference' => 'LOC-FIN-1', 'start_date' => now(), 'end_date' => now()->addDay(),
        'status' => 'active', 'subtotal' => 7777, 'total' => 7777,
    ]);
    Payment::create([
        'store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->employe->id,
        'reference' => 'PAY-FIN-1', 'amount' => 7777, 'method' => 'cash', 'type' => 'payment',
        'date' => now()->toDateString(),
    ]);
});

it('un employe n a pas les permissions financieres', function () {
    expect($this->employe->can('payments.view'))->toBeFalse();
    expect($this->employe->can('sales.view'))->toBeFalse();
    expect($this->employe->can('finance.view'))->toBeFalse();
    expect($this->employe->can('reports.view'))->toBeFalse();
    expect($this->employe->can('expenses.view'))->toBeFalse();
});

it('un employe ne peut pas ouvrir les pages financieres', function () {
    $this->actingAs($this->employe)->get(route('payments.index'))->assertForbidden();
    $this->actingAs($this->employe)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($this->employe)->get(route('sales.index'))->assertForbidden();
    $this->actingAs($this->employe)->get(route('expenses.index'))->assertForbidden();
});

it('le dashboard d un employe n expose aucun montant consolide', function () {
    $component = Livewire::actingAs($this->employe)->test(\App\Livewire\Dashboard::class);

    expect($component->viewData('canSeeFinance'))->toBeFalse();
    expect($component->viewData('revenueToday'))->toBe(0);
    expect($component->viewData('packRevenue'))->toBe(0);
    expect($component->viewData('packSavings'))->toBe(0);
    expect($component->viewData('recentPayments'))->toBeEmpty();
    expect(array_sum($component->viewData('chartRevenue')))->toBe(0);

    // Le montant ne doit pas non plus se trouver dans le HTML rendu.
    $this->actingAs($this->employe)->get(route('dashboard'))->assertOk()->assertDontSee('7 777');
});

it('un manager garde l acces aux donnees financieres', function () {
    $manager = User::create([
        'store_id' => $this->store->id, 'name' => 'Manager', 'email' => 'manager-finance@test.com',
        'password' => 'password', 'is_active' => true,
    ]);
    $manager->assignRole('manager');

    expect($manager->can('finance.view'))->toBeTrue();

    $component = Livewire::actingAs($manager)->test(\App\Livewire\Dashboard::class);

    expect($component->viewData('canSeeFinance'))->toBeTrue();
    expect($component->viewData('revenueToday'))->toBe(7777);
    expect($component->viewData('recentPayments'))->toHaveCount(1);
});

it('un caissier garde l acces aux encaissements qu il realise', function () {
    $caissier = User::create([
        'store_id' => $this->store->id, 'name' => 'Caissier', 'email' => 'caissier-finance@test.com',
        'password' => 'password', 'is_active' => true,
    ]);
    $caissier->assignRole('cashier');

    expect($caissier->can('finance.view'))->toBeTrue();
    expect($caissier->can('payments.view'))->toBeTrue();

    $this->actingAs($caissier)->get(route('payments.index'))->assertOk();
});

it('un magasinier ne voit pas les donnees financieres', function () {
    $magasinier = User::create([
        'store_id' => $this->store->id, 'name' => 'Magasinier', 'email' => 'magasinier-finance@test.com',
        'password' => 'password', 'is_active' => true,
    ]);
    $magasinier->assignRole('storekeeper');

    expect($magasinier->can('finance.view'))->toBeFalse();
    expect($magasinier->can('payments.view'))->toBeFalse();

    $this->actingAs($magasinier)->get(route('payments.index'))->assertForbidden();
});
