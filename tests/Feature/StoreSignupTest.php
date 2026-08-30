<?php

use App\Models\PlatformSetting;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Cache::forget(PlatformSetting::CACHE_KEY);

    $this->superAdmin = User::where('is_super_admin', true)->firstOrFail();
});

function signupPayload(array $overrides = []): array
{
    return array_merge([
        'store_name' => 'Élégance Location',
        'name' => 'Karim B.',
        'email' => 'karim@elegance.dz',
        'phone' => '0550 12 34 56',
        'wilaya' => 'Alger',
        'password' => 'motdepasse123',
        'password_confirmation' => 'motdepasse123',
        'conditions' => '1',
    ], $overrides);
}

it('affiche la landing page aux visiteurs avec les plans et l essai', function () {
    PlatformSetting::put('trial_days', 21);

    $this->get('/')
        ->assertOk()
        ->assertSee('Créer mon magasin')
        ->assertSee('21 jours')
        ->assertSee('PREMIUM');
});

it('redirige un utilisateur connecte vers son espace', function () {
    $store = Store::create(['name' => 'Mag', 'slug' => 'mag', 'token' => '', 'status' => 'active']);
    $user = User::create(['store_id' => $store->id, 'name' => 'U', 'email' => 'u@test.com', 'password' => 'password', 'is_active' => true]);
    \App\Services\SubscriptionService::createSubscription($store, \App\Models\Plan::where('slug', 'pro')->firstOrFail(), \App\Models\Subscription::STATUS_ACTIVE);
    \App\Models\StoreToken::issue($store->id);

    $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    $this->actingAs($this->superAdmin)->get('/')->assertRedirect(route('admin.index'));
});

it('en mode automatique le magasin est actif et la demo demarre', function () {
    PlatformSetting::put('signup_mode', PlatformSetting::MODE_AUTO);
    PlatformSetting::put('trial_days', 10);

    $this->post(route('store.register.store'), signupPayload())
        ->assertRedirect(route('dashboard'));

    $store = Store::where('slug', 'elegance-location')->firstOrFail();
    expect($store->status)->toBe('active');

    $user = User::where('email', 'karim@elegance.dz')->firstOrFail();
    expect($user->store_id)->toBe($store->id);
    expect($user->is_active)->toBeTrue();
    expect($user->hasRole('admin'))->toBeTrue();
    $this->assertAuthenticatedAs($user);

    $subscription = Subscription::where('store_id', $store->id)->firstOrFail();
    expect($subscription->status)->toBe(Subscription::STATUS_TRIAL);
    expect((int) now()->startOfDay()->diffInDays($subscription->trial_ends_at->startOfDay()))->toBe(10);

    // Le token est genere et affiche une seule fois.
    expect(session('new_token'))->toStartWith('STR-');
    expect(\App\Models\StoreToken::where('store_id', $store->id)->where('status', 'active')->exists())->toBeTrue();
});

it('en mode manuel la demande reste en attente', function () {
    PlatformSetting::put('signup_mode', PlatformSetting::MODE_MANUAL);

    $this->post(route('store.register.store'), signupPayload())
        ->assertRedirect(route('login'));

    $store = Store::where('slug', 'elegance-location')->firstOrFail();
    expect($store->status)->toBe('pending');
    expect(User::where('email', 'karim@elegance.dz')->firstOrFail()->is_active)->toBeFalse();
    expect(Subscription::where('store_id', $store->id)->exists())->toBeFalse();
    $this->assertGuest();
});

it('le super admin accepte une demande et la demo demarre a ce moment', function () {
    PlatformSetting::put('signup_mode', PlatformSetting::MODE_MANUAL);
    PlatformSetting::put('trial_days', 7);

    $this->post(route('store.register.store'), signupPayload());
    $store = Store::where('slug', 'elegance-location')->firstOrFail();

    $this->actingAs($this->superAdmin)
        ->post(route('admin.stores.approve', $store))
        ->assertRedirect();

    expect($store->refresh()->status)->toBe('active');
    expect(User::where('email', 'karim@elegance.dz')->firstOrFail()->is_active)->toBeTrue();

    $subscription = Subscription::where('store_id', $store->id)->firstOrFail();
    expect($subscription->status)->toBe(Subscription::STATUS_TRIAL);
    expect((int) now()->startOfDay()->diffInDays($subscription->trial_ends_at->startOfDay()))->toBe(7);
});

it('refuse une inscription incomplete ou avec un email deja pris', function () {
    User::create(['name' => 'Deja', 'email' => 'karim@elegance.dz', 'password' => 'password', 'is_active' => true]);

    $this->post(route('store.register.store'), signupPayload())
        ->assertSessionHasErrors('email');

    $this->post(route('store.register.store'), signupPayload(['email' => 'autre@test.dz', 'conditions' => null]))
        ->assertSessionHasErrors('conditions');

    expect(Store::where('slug', 'elegance-location')->exists())->toBeFalse();
});

it('l inscription peut etre fermee depuis les parametres generaux', function () {
    PlatformSetting::put('signup_enabled', false);

    $this->get(route('store.register'))->assertNotFound();
    $this->post(route('store.register.store'), signupPayload())->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee(route('store.register'));
});

it('le super admin modifie les parametres generaux', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\PlatformSettingsManager::class)
        ->set('signupMode', 'auto')
        ->set('trialDays', 30)
        ->set('signupEnabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(PlatformSetting::autoApproves())->toBeTrue();
    expect(PlatformSetting::trialDays())->toBe(30);
});

it('un admin de magasin ne peut pas ouvrir les parametres generaux', function () {
    $store = Store::create(['name' => 'Mag', 'slug' => 'mag2', 'token' => '', 'status' => 'active']);
    $user = User::create(['store_id' => $store->id, 'name' => 'U', 'email' => 'u2@test.com', 'password' => 'password', 'is_active' => true]);
    $user->assignRole('admin');

    // Abonnement valide, sinon c'est le controle d'abonnement qui repondrait en premier.
    \App\Services\SubscriptionService::createSubscription($store, \App\Models\Plan::where('slug', 'pro')->firstOrFail(), \App\Models\Subscription::STATUS_ACTIVE);
    \App\Models\StoreToken::issue($store->id);

    $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.stores.approve', $store))->assertForbidden();
});
