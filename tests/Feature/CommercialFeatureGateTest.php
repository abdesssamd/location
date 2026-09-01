<?php

use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StoreContext;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
});

it('bloque la gestion commerciale si le plan du magasin ne l inclut pas', function () {
    $store = Store::create(['name' => 'Basique', 'slug' => 'basique-com', 'token' => 'tok-basique-com', 'status' => 'active']);
    $basic = Plan::where('slug', 'basic')->firstOrFail();
    expect($basic->features)->not->toContain('commercial');

    SubscriptionService::createSubscription($store, $basic, Subscription::STATUS_ACTIVE, 0);
    \App\Models\StoreToken::issue($store->id);

    $user = User::create(['store_id' => $store->id, 'name' => 'Admin Basic', 'email' => 'admin-basic-com@test.com', 'password' => 'password', 'is_active' => true]);
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('sales.index'))->assertForbidden();
    $this->actingAs($user)->get(route('expenses.index'))->assertForbidden();
    $this->actingAs($user)->get(route('suppliers.index'))->assertForbidden();
    $this->actingAs($user)->get(route('purchases.index'))->assertForbidden();
});

it('autorise la gestion commerciale si le plan du magasin l inclut', function () {
    $store = Store::firstOrFail();
    $user = User::where('store_id', $store->id)->firstOrFail();
    StoreContext::set($store->id);

    expect(SubscriptionService::store($store->id)->hasFeature('commercial'))->toBeTrue();

    $this->actingAs($user)->get(route('sales.index'))->assertOk();
    $this->actingAs($user)->get(route('expenses.index'))->assertOk();
    $this->actingAs($user)->get(route('suppliers.index'))->assertOk();
    $this->actingAs($user)->get(route('purchases.index'))->assertOk();
});

it('le super admin n est jamais bloque par le plan d un magasin', function () {
    $store = Store::create(['name' => 'Basique 2', 'slug' => 'basique-com-2', 'token' => 'tok-basique-com-2', 'status' => 'active']);
    $basic = Plan::where('slug', 'basic')->firstOrFail();
    SubscriptionService::createSubscription($store, $basic, Subscription::STATUS_ACTIVE, 0);
    \App\Models\StoreToken::issue($store->id);

    $superAdmin = User::where('is_super_admin', true)->firstOrFail();
    StoreContext::set($store->id);

    $this->actingAs($superAdmin)->get(route('sales.index'))->assertOk();
});

it('le menu masque vente depenses et fournisseurs si le plan ne les inclut pas', function () {
    $store = Store::create(['name' => 'Basique 3', 'slug' => 'basique-com-3', 'token' => 'tok-basique-com-3', 'status' => 'active']);
    $basic = Plan::where('slug', 'basic')->firstOrFail();
    SubscriptionService::createSubscription($store, $basic, Subscription::STATUS_ACTIVE, 0);
    \App\Models\StoreToken::issue($store->id);

    $user = User::create(['store_id' => $store->id, 'name' => 'Admin Basic 3', 'email' => 'admin-basic-com-3@test.com', 'password' => 'password', 'is_active' => true]);
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('Ventes');
    $response->assertDontSee('Fournisseurs');
});
