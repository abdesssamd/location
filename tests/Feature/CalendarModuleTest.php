<?php

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

    $this->customerA = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $this->customerB = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Nadia', 'last_name' => 'Kaci', 'phone' => '0550 44 55 66']);

    $this->product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume Bleu', 'reference' => 'ART-CAL-1', 'rental_price' => 3000, 'caution_price' => 6000, 'quantity' => 5, 'status' => 'available']);

    $this->rentalA = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $this->customerA->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-CAL-A', 'start_date' => now()->addDay(), 'end_date' => now()->addDays(3),
        'status' => 'reserved', 'subtotal' => 3000, 'total' => 3000, 'paid_amount' => 1000,
    ]);
    RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $this->rentalA->id, 'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000]);

    $this->rentalB = Rental::create([
        'store_id' => $this->store->id, 'customer_id' => $this->customerB->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-CAL-B', 'start_date' => now()->addDays(5), 'end_date' => now()->addDays(6),
        'status' => 'active', 'subtotal' => 3000, 'total' => 3000, 'paid_amount' => 3000,
    ]);
});

it('affiche la page calendrier', function () {
    $this->actingAs($this->user)
        ->get(route('calendar'))
        ->assertOk()
        ->assertSee('Calendrier des locations');
});

it('filtre les evenements par statut', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class);

    $events = $component->viewData('events');
    expect(collect($events)->pluck('id'))->toContain($this->rentalA->id, $this->rentalB->id);

    $component->set('statuses', ['active']);

    $events = $component->viewData('events');
    expect(collect($events)->pluck('id'))->toContain($this->rentalB->id);
    expect(collect($events)->pluck('id'))->not->toContain($this->rentalA->id);
});

it('filtre les evenements par client', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class)
        ->set('customerId', $this->customerA->id);

    $events = $component->viewData('events');
    expect(collect($events)->pluck('id'))->toContain($this->rentalA->id);
    expect(collect($events)->pluck('id'))->not->toContain($this->rentalB->id);
});

it('filtre les evenements par article', function () {
    $autreProduit = Product::create(['store_id' => $this->store->id, 'name' => 'Robe', 'reference' => 'ART-CAL-2', 'rental_price' => 2000, 'caution_price' => 4000, 'quantity' => 3, 'status' => 'available']);
    RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $this->rentalB->id, 'product_id' => $autreProduit->id, 'quantity' => 1, 'unit_price' => 2000, 'line_total' => 2000]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class)
        ->set('resourceFilter', 'product-'.$this->product->id);

    $events = $component->viewData('events');
    expect(collect($events)->pluck('id'))->toContain($this->rentalA->id);
    expect(collect($events)->pluck('id'))->not->toContain($this->rentalB->id);
});

it('un pack apparait comme ressource et peut filtrer le calendrier', function () {
    $pack = Pack::create(['store_id' => $this->store->id, 'name' => 'Pack Test', 'reference' => 'PACK-CAL', 'pricing_mode' => Pack::PRICING_FIXED, 'pack_price' => 4000, 'status' => Pack::STATUS_ACTIVE]);
    PackItem::create(['store_id' => $this->store->id, 'pack_id' => $pack->id, 'product_id' => $this->product->id, 'quantity' => 1, 'selection_mode' => 'auto']);

    RentalItem::create(['store_id' => $this->store->id, 'rental_id' => $this->rentalB->id, 'product_id' => $this->product->id, 'pack_id' => $pack->id, 'pack_name' => $pack->name, 'quantity' => 1, 'unit_price' => 4000, 'line_total' => 4000, 'is_pack_component' => true]);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class);

    $resources = $component->viewData('resources');
    expect(collect($resources)->pluck('id'))->toContain('pack-'.$pack->id);

    $component->set('resourceFilter', 'pack-'.$pack->id);
    $events = $component->viewData('events');
    expect(collect($events)->pluck('id'))->toContain($this->rentalB->id);
    expect(collect($events)->pluck('id'))->not->toContain($this->rentalA->id);
});

it('affiche un apercu de location sans quitter le calendrier', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class)
        ->call('previewRental', $this->rentalA->id)
        ->assertSet('previewRentalId', $this->rentalA->id)
        ->assertSee('LOC-CAL-A')
        ->assertSee('Ali Cherif');
});

it('l apercu ne fuit pas une location dun autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre', 'slug' => 'autre-cal', 'token' => 'tok-cal', 'status' => 'active']);
    StoreContext::set($otherStore->id);
    $otherCustomer = Customer::create(['store_id' => $otherStore->id, 'first_name' => 'X', 'last_name' => 'Y', 'phone' => '0000']);
    $otherRental = Rental::create([
        'store_id' => $otherStore->id, 'customer_id' => $otherCustomer->id, 'user_id' => $this->user->id,
        'reference' => 'LOC-OTHER', 'start_date' => now(), 'end_date' => now()->addDay(),
        'status' => 'active', 'subtotal' => 1000, 'total' => 1000,
    ]);
    StoreContext::set($this->store->id);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class)
        ->call('previewRental', $otherRental->id)
        ->assertDontSee('LOC-OTHER');
});

it('reinitialise les filtres', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Calendar::class)
        ->set('statuses', ['active'])
        ->set('customerId', $this->customerA->id)
        ->call('resetFilters')
        ->assertSet('customerId', null)
        ->assertSet('statuses', ['reserved', 'active', 'completed']);
});

it('le clic sur une date vide du calendrier pre-remplit la reservation', function () {
    $start = now()->addDays(10)->toDateString();
    $end = now()->addDays(12)->toDateString();

    Livewire::actingAs($this->user)
        ->withQueryParams(['start' => $start, 'end' => $end])
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->assertSet('start_date', $start)
        ->assertSet('end_date', $end);
});
