<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Rental;
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
});

it('affiche le tableau de bord avec les KPIs', function () {
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tableau de bord')
        ->assertSee('LOC-2026-0001');
});

it('affiche les rapports avec le chiffre d affaires', function () {
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);
    Payment::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->user->id, 'reference' => 'PAY-2026-0001', 'amount' => 1500, 'method' => 'cash', 'type' => 'payment', 'date' => now()]);

    $this->actingAs($this->user)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee("Chiffre d'affaires", false);
});

it('exporte les paiements en CSV', function () {
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);
    Payment::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->user->id, 'reference' => 'PAY-2026-0001', 'amount' => 1500, 'method' => 'cash', 'type' => 'payment', 'date' => now()]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Reports\Reports::class)
        ->call('exportPaymentsCsv')
        ->assertFileDownloaded('paiements-'.now()->format('Ymd').'.csv');
});
it('inclut les paiements du jour meme dans la periode du rapport', function () {
    // Bug precedemment corrige : Payment.date (cast 'date') se serialise en
    // "Y-m-d H:i:s" a l'ecriture ; comparer whereBetween('date', [.., "aujourd'hui"])
    // excluait a tort les paiements du jour meme, la comparaison de chaines
    // "2026-08-31 00:00:00" <= "2026-08-31" etant fausse.
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0099', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 5000, 'total' => 5000]);
    Payment::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->user->id, 'reference' => 'PAY-2026-0099', 'amount' => 5000, 'method' => 'cash', 'type' => 'payment', 'date' => now()->toDateString()]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Reports\Reports::class)
        ->set('from', now()->startOfYear()->toDateString())
        ->set('to', now()->toDateString());

    expect($component->viewData('revenue'))->toBe(5000);
});
