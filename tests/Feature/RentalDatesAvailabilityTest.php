<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
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
});

it('decale la date de fin quand la date de debut change', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('start_date', '2026-09-22')
        ->set('end_date', '2026-09-23');

    // Duree d'un jour conservee : la fin suit le nouveau debut.
    $component->set('start_date', '2026-10-05');

    expect($component->get('end_date'))->toBe('2026-10-06');
});

it('conserve la duree choisie quand la date de debut change', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('start_date', '2026-09-22')
        ->set('end_date', '2026-09-26'); // 4 jours

    $component->set('start_date', '2026-10-01');

    expect($component->get('end_date'))->toBe('2026-10-05');
});

it('corrige une date de fin anterieure au debut', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('start_date', '2026-09-22')
        ->set('end_date', '2026-09-10');

    expect($component->get('end_date'))->toBe('2026-09-23');
});

it('signale un article devenu indisponible apres un changement de dates', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume unique', 'reference' => 'ART-UNIQ', 'rental_price' => 3000, 'caution_price' => 0, 'quantity' => 1, 'status' => 'available']);

    $busyStart = now()->addDays(30)->toDateString();
    $busyEnd = now()->addDays(32)->toDateString();

    // Une autre location occupe deja l'unique exemplaire sur ces dates.
    $other = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-BUSY', 'start_date' => $busyStart, 'end_date' => $busyEnd,
        'status' => 'reserved', 'subtotal' => 3000, 'total' => 3000,
    ]);
    RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $other->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('start_date', now()->addDays(60)->toDateString())
        ->set('end_date', now()->addDays(61)->toDateString())
        ->call('addProduct', $product->id);

    expect($component->viewData('unavailableItems'))->toBeEmpty();

    // On bascule sur les dates deja prises : l'article doit etre signale.
    $component->set('start_date', $busyStart);
    $component->set('end_date', $busyEnd);

    $unavailable = $component->viewData('unavailableItems');

    expect($unavailable)->toHaveCount(1);
    expect($unavailable[0]['name'])->toBe('Costume unique');
    expect($unavailable[0]['free'])->toBe(0);
});

it('enregistre le prix pack complet et non celui d un seul composant', function () {
    // Reproduit le cas signale : pack a prix fixe compose de deux lignes
    // « au choix », le total enregistre doit valoir le prix du pack entier.
    $catChaussure = Category::create(['store_id' => $this->store->id, 'name' => 'Chaussure']);
    $catCostume = Category::create(['store_id' => $this->store->id, 'name' => 'Costume']);

    $chaussure = Product::create(['store_id' => $this->store->id, 'name' => 'CHAUSURE pp', 'reference' => 'ART-000005-42', 'category_id' => $catChaussure->id, 'rental_price' => 1500, 'caution_price' => 0, 'quantity' => 2, 'status' => 'available']);
    $costume = Product::create(['store_id' => $this->store->id, 'name' => 'cos', 'reference' => 'ART-000001-36', 'category_id' => $catCostume->id, 'rental_price' => 3000, 'caution_price' => 0, 'quantity' => 2, 'status' => 'available']);

    $pack = Pack::create([
        'store_id' => $this->store->id, 'name' => 'PACK', 'reference' => 'PACK-TOTAL',
        'pricing_mode' => Pack::PRICING_FIXED, 'pack_price' => 4500, 'caution' => 0,
        'status' => Pack::STATUS_ACTIVE,
    ]);
    $piChaussure = PackItem::create(['pack_id' => $pack->id, 'category_id' => $catChaussure->id, 'quantity' => 1, 'selection_mode' => 'auto']);
    $piCostume = PackItem::create(['pack_id' => $pack->id, 'category_id' => $catCostume->id, 'quantity' => 1, 'selection_mode' => 'auto']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $this->customer->id)
        ->set('start_date', now()->addDays(18)->toDateString())
        ->set('end_date', now()->addDays(19)->toDateString())
        ->set('packs', [[
            'pack_id' => $pack->id,
            'quantity' => 1,
            'selected_products' => [
                $piChaussure->id => $chaussure->id,
                $piCostume->id => $costume->id,
            ],
        ]])
        ->call('save');

    $rental = Rental::where('customer_id', $this->customer->id)->latest('id')->firstOrFail();

    expect($rental->total)->toBe(4500);
    expect($rental->subtotal)->toBe(4500);
    expect((int) $rental->items->sum('line_total'))->toBe(4500);
});
