<?php

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Rental;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::where('slug', 'demo')->firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    $this->superAdmin = User::where('is_super_admin', true)->firstOrFail();
    StoreContext::set($this->store->id);
});

it('le super admin cree un plan avec ses limites', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\PlanManager::class)
        ->set('name', 'STARTER')
        ->set('price', '2000')
        ->set('max_products', '20')
        ->set('max_customers', '10')
        ->set('max_users', '1')
        ->set('max_rentals_per_month', '15')
        ->call('save')
        ->assertHasNoErrors();

    $plan = Plan::where('name', 'STARTER')->firstOrFail();
    expect($plan->slug)->toBe('starter');
    expect($plan->max_products)->toBe(20);
    expect($plan->max_rentals_per_month)->toBe(15);
});

it('le super admin modifie un plan existant', function () {
    $plan = Plan::where('slug', 'basic')->firstOrFail();

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\PlanManager::class)
        ->call('openEdit', $plan->id)
        ->set('price', '1800')
        ->set('max_rentals_per_month', '25')
        ->call('save')
        ->assertHasNoErrors();

    expect($plan->refresh()->price)->toBe(1800);
    expect($plan->max_rentals_per_month)->toBe(25);
    // Le slug ne change pas a l'edition.
    expect($plan->slug)->toBe('basic');
});

it('un utilisateur de magasin ne peut pas gerer les plans', function () {
    $this->actingAs($this->user)->get(route('admin.plans.index'))->assertForbidden();
});

it('active une promotion avec prix barre et etiquette', function () {
    $plan = Plan::where('slug', 'pro')->firstOrFail();

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\PlanManager::class)
        ->call('openEdit', $plan->id)
        ->set('promo_enabled', true)
        ->set('promo_price', '2000')
        ->set('promo_label', 'Lancement')
        ->call('save')
        ->assertHasNoErrors();

    $plan->refresh();
    expect($plan->promo_price)->toBe(2000);
    expect($plan->promo_label)->toBe('Lancement');
    expect($plan->hasActivePromo())->toBeTrue();
    expect($plan->effectivePrice())->toBe(2000);
});

it('refuse un prix promo superieur ou egal au prix normal', function () {
    $plan = Plan::where('slug', 'pro')->firstOrFail();

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\PlanManager::class)
        ->call('openEdit', $plan->id)
        ->set('promo_enabled', true)
        ->set('promo_price', (string) $plan->price)
        ->call('save')
        ->assertHasErrors('promo_price');
});

it('une promotion expiree ne s applique plus', function () {
    $plan = Plan::where('slug', 'pro')->firstOrFail();
    $plan->update(['promo_price' => 1000, 'promo_ends_at' => now()->subDay()]);

    expect($plan->hasActivePromo())->toBeFalse();
    expect($plan->effectivePrice())->toBe($plan->price);
});

it('retire la promotion d un plan', function () {
    $plan = Plan::where('slug', 'pro')->firstOrFail();
    $plan->update(['promo_price' => 1000, 'promo_label' => 'Test']);

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\PlanManager::class)
        ->call('clearPromo', $plan->id);

    expect($plan->refresh()->promo_price)->toBeNull();
});

it('la landing page affiche le prix promo barre', function () {
    $plan = Plan::where('slug', 'pro')->firstOrFail();
    $plan->update(['promo_price' => 1999, 'promo_label' => 'Offre de lancement', 'is_active' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Offre de lancement')
        ->assertSee('1 999')
        ->assertSee('3 000');
});

it('la page des plans abonnement affiche aussi la promo', function () {
    $plan = Plan::where('slug', 'basic')->firstOrFail();
    $plan->update(['promo_price' => 999, 'promo_label' => 'Essai']);

    $this->actingAs($this->user)
        ->get(route('subscription.plans'))
        ->assertOk()
        ->assertSee('Essai')
        ->assertSee('999');
});

it('la souscription hors ligne facture le prix promo si actif', function () {
    $plan = Plan::where('slug', 'basic')->firstOrFail();
    $plan->update(['promo_price' => 999]);

    $this->actingAs($this->user)->post(route('subscription.subscribe', $plan));

    $payment = \App\Models\SubscriptionPayment::where('store_id', $this->store->id)->latest()->first();
    expect($payment->amount)->toBe(999);
});

it('bloque la creation de reservation au dela de la limite mensuelle du plan', function () {
    $plan = Plan::where('slug', 'basic')->firstOrFail();
    $plan->update(['max_rentals_per_month' => 1]);

    \App\Models\Subscription::where('store_id', $this->store->id)->update(['plan_id' => $plan->id, 'status' => 'active', 'ends_at' => now()->addMonth()]);

    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0550']);
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-LIM-1', 'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 5, 'status' => 'available']);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(2)->toDateString())
        ->set('items', [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]])
        ->call('save');

    expect(Rental::where('store_id', $this->store->id)->count())->toBe(1);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Rentals\RentalForm::class)
        ->set('customer_id', $customer->id)
        ->set('start_date', now()->addDay()->toDateString())
        ->set('end_date', now()->addDays(2)->toDateString())
        ->set('items', [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]])
        ->call('save');

    expect(Rental::where('store_id', $this->store->id)->count())->toBe(1);
});

it('un plan sans limite de locations permet des reservations illimitees', function () {
    $plan = Plan::where('slug', 'premium')->firstOrFail();
    expect($plan->max_rentals_per_month)->toBeNull();

    \App\Models\Subscription::where('store_id', $this->store->id)->update(['plan_id' => $plan->id, 'status' => 'active', 'ends_at' => now()->addMonth()]);

    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0551']);
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-LIM-2', 'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 5, 'status' => 'available']);

    for ($i = 0; $i < 3; $i++) {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Rentals\RentalForm::class)
            ->set('customer_id', $customer->id)
            ->set('start_date', now()->addDays($i + 1)->toDateString())
            ->set('end_date', now()->addDays($i + 2)->toDateString())
            ->set('items', [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]])
            ->call('save');
    }

    expect(Rental::where('store_id', $this->store->id)->count())->toBe(3);
});
