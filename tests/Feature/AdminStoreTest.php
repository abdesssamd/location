<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
});

it('super admin peut acceder a la liste des magasins', function () {
    $admin = User::where('is_super_admin', true)->first();

    $this->actingAs($admin)
        ->get(route('admin.stores.index'))
        ->assertOk()
        ->assertSee('Magasins');
});

it('un utilisateur normal ne peut pas acceder a ladm in', function () {
    $store = Store::firstOrFail();
    $user = User::where('store_id', $store->id)->firstOrFail();

    $this->actingAs($user)
        ->get(route('admin.stores.index'))
        ->assertForbidden();
});

it('le super admin peut creer un magasin', function () {
    $admin = User::where('is_super_admin', true)->first();

    $this->actingAs($admin)
        ->post(route('admin.stores.store'), [
            'name' => 'Nouveau Magasin',
            'slug' => 'nouveau-magasin',
            'currency' => 'DA',
        ])
        ->assertRedirect();

    expect(Store::where('slug', 'nouveau-magasin')->exists())->toBeTrue();
});

it('le super admin cree un admin de magasin avec confirmation du mot de passe', function () {
    $superAdmin = User::where('is_super_admin', true)->first();
    $store = Store::create(['name' => 'Mag Admin', 'slug' => 'mag-admin', 'token' => 'tok-ma', 'status' => 'active']);

    $this->actingAs($superAdmin)
        ->post(route('admin.stores.admins.store', $store), [
            'name' => 'Younes',
            'email' => 'younes@magadmin.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $created = User::where('email', 'younes@magadmin.com')->firstOrFail();
    expect($created->store_id)->toBe($store->id);
    expect($created->hasRole('admin'))->toBeTrue();
});

it('refuse un admin de magasin si la confirmation differe', function () {
    $superAdmin = User::where('is_super_admin', true)->first();
    $store = Store::create(['name' => 'Mag Admin 2', 'slug' => 'mag-admin-2', 'token' => 'tok-ma2', 'status' => 'active']);

    $this->actingAs($superAdmin)
        ->post(route('admin.stores.admins.store', $store), [
            'name' => 'Test',
            'email' => 'test@magadmin.com',
            'password' => 'secret1234',
            'password_confirmation' => 'different999',
        ])
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'test@magadmin.com')->exists())->toBeFalse();
});

it('un gestionnaire magasin voit uniquement son equipe', function () {
    $storeA = Store::create(['name' => 'A', 'slug' => 'aa', 'token' => 'ta', 'status' => 'active']);
    $storeB = Store::create(['name' => 'B', 'slug' => 'bb', 'token' => 'tb', 'status' => 'active']);

    // Token + abonnement actif requis par le middleware CheckSubscription
    \App\Models\StoreToken::create(['store_id' => $storeA->id, 'token' => 'ta', 'status' => 'active']);
    $plan = \App\Models\Plan::where('slug', 'pro')->firstOrFail();
    \App\Services\SubscriptionService::createSubscription($storeA, $plan, \App\Models\Subscription::STATUS_ACTIVE, 0);

    $userA = User::create(['store_id' => $storeA->id, 'name' => 'A', 'email' => 'a@x.com', 'password' => 'password']);
    $userA->assignRole('admin');
    $userB = User::create(['store_id' => $storeB->id, 'name' => 'B', 'email' => 'b@x.com', 'password' => 'password']);

    \App\Services\StoreContext::set($storeA->id);

    $this->actingAs($userA)
        ->get(route('team.index'))
        ->assertOk()
        ->assertSee('a@x.com')
        ->assertDontSee('b@x.com');
});