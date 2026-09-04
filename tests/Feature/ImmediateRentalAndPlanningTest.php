<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\StockMovement;
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
    $this->customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0550']);
    $this->product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-IMM-1', 'rental_price' => 3000, 'caution_price' => 0, 'quantity' => 2, 'status' => 'available']);
});

it('cree une location immediate deja active avec sortie de stock', function () {
    Livewire::actingAs($this->user)
        ->withQueryParams(['immediate' => 1])
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('items', [[
            'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000,
            'pack_id' => null, 'pack_name' => null, 'is_pack_component' => false,
        ]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    expect($rental->status)->toBe('active');
    expect(StockMovement::where('product_id', $this->product->id)->where('type', 'out')->count())->toBe(1);
});

it('cree une reservation sans sortie de stock par defaut', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('items', [[
            'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000,
            'pack_id' => null, 'pack_name' => null, 'is_pack_component' => false,
        ]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    expect($rental->status)->toBe('reserved');
    expect(StockMovement::where('product_id', $this->product->id)->where('type', 'out')->count())->toBe(0);
});

it('refuse la location immediate sans le droit de sortie', function () {
    // Le caissier peut reserver mais pas sortir les articles.
    $caissier = User::create(['store_id' => $this->store->id, 'name' => 'Caissier', 'email' => 'caissier-imm@test.com', 'password' => 'password', 'is_active' => true]);
    $caissier->assignRole('cashier');

    expect($caissier->can('rentals.checkout'))->toBeFalse();

    $component = Livewire::actingAs($caissier)
        ->withQueryParams(['immediate' => 1])
        ->test(\App\Livewire\Rentals\RentalForm::class);

    expect($component->get('immediate'))->toBeFalse();

    $component->set('customer_id', $this->customer->id)
        ->set('items', [[
            'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000,
            'pack_id' => null, 'pack_name' => null, 'is_pack_component' => false,
        ]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    expect($rental->status)->toBe('reserved');
});

it('ignore une tentative de forcer immediate depuis le client', function () {
    $caissier = User::create(['store_id' => $this->store->id, 'name' => 'Caissier 2', 'email' => 'caissier-imm2@test.com', 'password' => 'password', 'is_active' => true]);
    $caissier->assignRole('cashier');

    Livewire::actingAs($caissier)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('immediate', true) // propriété publique : forcée côté client
        ->set('customer_id', $this->customer->id)
        ->set('items', [[
            'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000,
            'pack_id' => null, 'pack_name' => null, 'is_pack_component' => false,
        ]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    expect($rental->status)->toBe('reserved');
    expect(StockMovement::where('product_id', $this->product->id)->where('type', 'out')->count())->toBe(0);
});

it('un employe peut creer une location', function () {
    $employe = User::create(['store_id' => $this->store->id, 'name' => 'Employe', 'email' => 'employe-imm@test.com', 'password' => 'password', 'is_active' => true]);
    $employe->assignRole('employee');

    expect($employe->can('rentals.create'))->toBeTrue();
    expect($employe->can('rentals.checkout'))->toBeTrue();

    $this->actingAs($employe)->get(route('rentals.create'))->assertOk();
});

it('affiche le planning de disponibilite d un article', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductShow::class, ['product' => $this->product]);

    $days = $component->viewData('planningDays');

    expect($days)->not->toBeEmpty();
    expect($days[0])->toHaveKeys(['date', 'day', 'free', 'is_free', 'is_past', 'is_today']);

    // Aucune location : tous les jours du mois sont libres.
    expect(collect($days)->every(fn ($d) => $d['free'] === 2))->toBeTrue();
});

it('marque comme complet un jour deja entierement reserve', function () {
    $busyDay = now()->addDays(10);

    $rental = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-PLAN-1', 'start_date' => $busyDay->toDateString(), 'end_date' => $busyDay->copy()->addDay()->toDateString(),
        'status' => 'reserved', 'subtotal' => 6000, 'total' => 6000,
    ]);
    // Les deux exemplaires sont pris ce jour-la.
    RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 3000, 'line_total' => 6000]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductShow::class, ['product' => $this->product])
        ->set('planningMonth', $busyDay->format('Y-m'));

    $days = collect($component->viewData('planningDays'));
    $busy = $days->firstWhere('date', $busyDay->toDateString());

    expect($busy['free'])->toBe(0);
    expect($busy['is_free'])->toBeFalse();
});

it('navigue entre les mois du planning', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductShow::class, ['product' => $this->product])
        ->set('planningMonth', '2026-09');

    $component->call('nextMonth');
    expect($component->get('planningMonth'))->toBe('2026-10');

    $component->call('previousMonth');
    expect($component->get('planningMonth'))->toBe('2026-09');

    $component->call('goToToday');
    expect($component->get('planningMonth'))->toBe(now()->format('Y-m'));
});

it('tient compte de la quantite recherchee dans le planning', function () {
    $busyDay = now()->addDays(12);

    $rental = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-PLAN-2', 'start_date' => $busyDay->toDateString(), 'end_date' => $busyDay->copy()->addDay()->toDateString(),
        'status' => 'reserved', 'subtotal' => 3000, 'total' => 3000,
    ]);
    // Un seul des deux exemplaires est pris.
    RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\ProductShow::class, ['product' => $this->product])
        ->set('planningMonth', $busyDay->format('Y-m'));

    $day = collect($component->viewData('planningDays'))->firstWhere('date', $busyDay->toDateString());
    expect($day['free'])->toBe(1);
    expect($day['is_free'])->toBeTrue(); // il en reste un

    // En demandant deux exemplaires, ce jour ne convient plus.
    $component->set('planningQuantity', 2);
    $day = collect($component->viewData('planningDays'))->firstWhere('date', $busyDay->toDateString());
    expect($day['is_free'])->toBeFalse();
});
