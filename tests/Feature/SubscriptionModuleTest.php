<?php

use App\Models\Plan;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\StoreContext;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::where('slug', 'demo')->firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    $this->superAdmin = User::where('is_super_admin', true)->firstOrFail();
    StoreContext::set($this->store->id);

    $this->subscription = Subscription::where('store_id', $this->store->id)->firstOrFail();
});

it('cree les trois plans par defaut', function () {
    expect(Plan::count())->toBe(3);
    expect(Plan::where('slug', 'basic')->first()->price)->toBe(1500);
    expect(Plan::where('slug', 'pro')->first()->is_popular)->toBeTrue();
    expect(Plan::where('slug', 'premium')->first()->features)->toContain('api');
});

it('le service repond sur l etat de l abonnement', function () {
    $service = SubscriptionService::store($this->store->id);

    expect($service->isActive())->toBeTrue();
    expect($service->isExpired())->toBeFalse();
    expect($service->status())->toBe(Subscription::STATUS_ACTIVE);
    expect($service->daysRemaining())->toBeGreaterThanOrEqual(29);
});

it('verifie les fonctionnalites par plan', function () {
    $service = SubscriptionService::store($this->store->id);

    // PRO : packs et qr_code inclus, pas d'API
    expect($service->hasFeature('packs'))->toBeTrue();
    expect($service->hasFeature('qr_code'))->toBeTrue();
    expect($service->hasFeature('api'))->toBeFalse();
});

it('detecte un abonnement expire', function () {
    $this->subscription->update(['status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->subDays(10)]);

    $service = SubscriptionService::store($this->store->id);
    expect($service->isExpired())->toBeTrue();
    expect($service->isActive())->toBeFalse();
});

it('bloque l acces au dela de la periode de grace', function () {
    $this->subscription->update(['status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->subDays(5)]);

    $this->actingAs($this->user)
        ->get(route('products.index'))
        ->assertRedirect(route('subscription.index'));

    $this->followRedirects($this->actingAs($this->user)->get(route('products.index')))
        ->assertOk()
        ->assertSee('expiré', false);
});

it('autorise l acces pendant la periode de grace avec alerte', function () {
    $this->subscription->update(['status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->subDay()]);

    $service = SubscriptionService::store($this->store->id);
    expect($service->isExpired())->toBeTrue();
    expect($service->inGrace())->toBeTrue();

    $this->actingAs($this->user)
        ->get(route('products.index'))
        ->assertOk();
});

it('avertit avant expiration proche', function () {
    $this->subscription->update(['status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->addDays(3)]);

    $service = SubscriptionService::store($this->store->id);
    expect($service->expiresSoon())->toBeTrue();
    expect($service->warningThreshold())->toBe(3);
});

it('bloque la creation d articles a la limite du plan', function () {
    $basic = Plan::where('slug', 'basic')->first();
    $this->subscription->update(['plan_id' => $basic->id]);

    $service = SubscriptionService::store($this->store->id);
    // Le magasin démo contient déjà des articles seedés ; la limite BASIC est 100.
    // On teste la logique avec une limite artificielle.
    $basic->update(['max_products' => 0]);
    $service = SubscriptionService::store($this->store->id);

    expect($service->canCreateProduct())->toBeFalse();
    expect($service->limitMessage('product'))->toContain('Limite atteinte');
});

it('renouvelle sans perdre les jours restants', function () {
    $this->subscription->update(['status' => Subscription::STATUS_ACTIVE, 'ends_at' => now()->addDays(20)]);

    $pro = Plan::where('slug', 'pro')->first();
    $sub = SubscriptionService::renew($this->store, $pro, $this->superAdmin->id);

    // 20 jours restants + 1 mois (~30-31 j) => environ 50 jours
    $expected = now()->addDays(20)->addMonth();
    expect($sub->ends_at->format('Y-m-d'))->toBe($expected->format('Y-m-d'));
    expect($sub->status)->toBe(Subscription::STATUS_ACTIVE);
});

it('renouvelle depuis aujourd hui si deja expire', function () {
    $this->subscription->update(['status' => Subscription::STATUS_EXPIRED, 'ends_at' => now()->subDays(15)]);

    $pro = Plan::where('slug', 'pro')->first();
    $sub = SubscriptionService::renew($this->store, $pro, $this->superAdmin->id);

    expect($sub->ends_at->format('Y-m-d'))->toBe(now()->addMonth()->format('Y-m-d'));
});

it('refuse un changement de plan si les limites sont depassees', function () {
    $basic = Plan::where('slug', 'basic')->first();
    $basic->update(['max_products' => 2]);

    // Le magasin possède 3 articles -> dépasse la limite BASIC
    foreach ([1, 2, 3] as $i) {
        \App\Models\Product::create(['store_id' => $this->store->id, 'name' => 'Article '.$i, 'reference' => 'LIM-00'.$i, 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);
    }

    [$ok, $message] = SubscriptionService::changePlan($this->store, $basic, $this->superAdmin->id);

    expect($ok)->toBeFalse();
    expect($message)->toContain('Impossible de passer à ce plan');
});

it('applique un changement de plan valide', function () {
    $premium = Plan::where('slug', 'premium')->first();

    [$ok, $message] = SubscriptionService::changePlan($this->store, $premium, $this->superAdmin->id);

    expect($ok)->toBeTrue();
    expect($this->subscription->refresh()->plan_id)->toBe($premium->id);
    expect(SubscriptionHistory::where('store_id', $this->store->id)->where('action', 'upgraded')->exists())->toBeTrue();
});

it('regenere le token et invalide l ancien', function () {
    $oldToken = $this->store->refresh()->token;

    $new = SubscriptionService::generateToken($this->store, $this->superAdmin->id);

    expect($new->token)->not->toBe($oldToken);
    expect($new->token)->toStartWith('STR-');
    expect(StoreToken::where('store_id', $this->store->id)->where('status', 'active')->count())->toBe(1);
    expect(StoreToken::where('store_id', $this->store->id)->where('token', $oldToken)->where('status', 'revoked')->exists())->toBeTrue();
    expect($this->store->refresh()->token)->toBe($new->token);
});

it('approuve un paiement hors ligne et active l abonnement', function () {
    $premium = Plan::where('slug', 'premium')->first();

    $payment = SubscriptionPayment::create([
        'store_id' => $this->store->id,
        'plan_id' => $premium->id,
        'amount' => $premium->price,
        'method' => 'baridimob',
        'status' => SubscriptionPayment::STATUS_PENDING,
        'reference' => 'SUB-TEST-1',
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.subscriptions.approve', $payment))
        ->assertRedirect();

    expect($payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_APPROVED);
    expect($this->subscription->refresh()->plan_id)->toBe($premium->id);
    expect($this->subscription->status)->toBe(Subscription::STATUS_ACTIVE);
    expect(SubscriptionHistory::where('store_id', $this->store->id)->where('action', 'renewed')->exists())->toBeTrue();
});

it('refuse un paiement hors ligne', function () {
    $payment = SubscriptionPayment::create([
        'store_id' => $this->store->id,
        'plan_id' => Plan::first()->id,
        'amount' => 1500,
        'method' => 'ccp',
        'status' => SubscriptionPayment::STATUS_PENDING,
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.subscriptions.reject', $payment))
        ->assertRedirect();

    expect($payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_REJECTED);
    expect($payment->reviewed_by)->toBe($this->superAdmin->id);
});

it('le magasin consulte sa page abonnement', function () {
    $this->actingAs($this->user)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertSee('Mon abonnement')
        ->assertSee('PRO');
});

it('la page plans affiche les cartes et la comparaison', function () {
    $this->actingAs($this->user)
        ->get(route('subscription.plans'))
        ->assertOk()
        ->assertSee('BASIC')
        ->assertSee('PREMIUM')
        ->assertSee('Populaire');
});

it('le super admin voit la page abonnements', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.subscriptions.index'))
        ->assertOk()
        ->assertSee('Abonnements SaaS');
});

it('un magasin suspendu est bloque', function () {
    $this->store->update(['status' => 'suspended']);

    $this->actingAs($this->user)
        ->get(route('products.index'))
        ->assertRedirect(route('subscription.index'));
});

it('le super admin n est pas soumis a l abonnement', function () {
    $this->subscription->update(['status' => Subscription::STATUS_EXPIRED, 'ends_at' => now()->subDays(30)]);

    $this->actingAs($this->superAdmin)
        ->get(route('products.index'))
        ->assertOk();
});