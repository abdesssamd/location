<?php

use App\Models\Customer;
use App\Models\Pack;
use App\Models\PackImage;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\User;
use App\Services\StoreContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
| Tests de non-régression des failles corrigées lors de l'audit du 30/08/2026.
| Chaque test porte la référence du constat (C-1 … M-5).
*/

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->storeA = Store::create(['name' => 'Magasin A', 'slug' => 'mag-a', 'token' => 'tok-a', 'status' => 'active']);
    $this->storeB = Store::create(['name' => 'Magasin B', 'slug' => 'mag-b', 'token' => 'tok-b', 'status' => 'active']);

    $this->apiTokens = [];

    foreach ([$this->storeA, $this->storeB] as $store) {
        $this->apiTokens[$store->slug] = StoreToken::issue($store->id)->plainText;
        \App\Services\SubscriptionService::createSubscription($store, \App\Models\Plan::where('slug', 'premium')->firstOrFail(), 'active');
    }

    $this->adminA = User::create(['store_id' => $this->storeA->id, 'name' => 'Admin A', 'email' => 'admin-a@test.com', 'password' => 'password', 'is_active' => true]);
    $this->adminA->assignRole('admin');

    $this->adminB = User::create(['store_id' => $this->storeB->id, 'name' => 'Admin B', 'email' => 'admin-b@test.com', 'password' => 'password', 'is_active' => true]);
    $this->adminB->assignRole('admin');

    StoreContext::set($this->storeB->id);
    $this->productB = Product::create(['store_id' => $this->storeB->id, 'name' => 'Costume B', 'reference' => 'B-1', 'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 3, 'status' => 'available']);
    $this->packB = Pack::create(['store_id' => $this->storeB->id, 'name' => 'Pack B', 'reference' => 'PACK-B', 'pricing_mode' => 'fixed', 'pack_price' => 1000, 'status' => 'active']);
    $this->packImageB = PackImage::create(['store_id' => $this->storeB->id, 'pack_id' => $this->packB->id, 'path' => 'packs/b.webp', 'is_primary' => true, 'sort_order' => 0]);

    StoreContext::set($this->storeA->id);
});

it('C-1 : un token API ne donne acces qu aux donnees de son magasin', function () {
    $response = $this->withHeader('Authorization', 'Bearer '.$this->apiTokens['mag-a'])
        ->getJson('/api/products/'.$this->productB->id);

    $response->assertNotFound();

    $this->withHeader('Authorization', 'Bearer '.$this->apiTokens['mag-b'])
        ->getJson('/api/products/'.$this->productB->id)
        ->assertOk()
        ->assertJsonPath('id', $this->productB->id);
});

it('C-1 : l API refuse une requete sans token', function () {
    $this->getJson('/api/products')->assertUnauthorized();
});

it('C-2 : un admin de magasin ne peut pas basculer sur un autre magasin', function () {
    Livewire::actingAs($this->adminA)
        ->test(\App\Livewire\Settings\StoreSettings::class)
        ->set('selectedStoreId', $this->storeB->id)
        ->call('selectStore')
        ->assertForbidden();

    expect($this->storeB->refresh()->name)->toBe('Magasin B');
});

it('C-3 : un admin ne peut pas modifier un utilisateur d un autre magasin', function () {
    // Le compte de l'autre magasin n'est même pas résolvable : 404, pas de fuite d'existence.
    expect(fn () => Livewire::actingAs($this->adminA)
        ->test(\App\Livewire\Team\ManageUsers::class)
        ->call('openEdit', $this->adminB->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($this->adminA)
        ->test(\App\Livewire\Team\ManageUsers::class)
        ->call('deleteUser', $this->adminB->id))
        ->toThrow(ModelNotFoundException::class);

    expect(User::whereKey($this->adminB->id)->exists())->toBeTrue();
});

it('C-3 : un employe ne peut pas se promouvoir administrateur', function () {
    $employe = User::create(['store_id' => $this->storeA->id, 'name' => 'Employe', 'email' => 'emp@test.com', 'password' => 'password', 'is_active' => true]);
    $employe->assignRole('employee');
    $employe->givePermissionTo('users.manage');

    Livewire::actingAs($employe)
        ->test(\App\Livewire\Team\ManageUsers::class)
        ->set('name', 'Nouveau')
        ->set('email', 'nouveau@test.com')
        ->set('password', 'password123')
        ->set('role', 'admin')
        ->call('save')
        ->assertForbidden();
});

it('C-4 : la recherche d equipe ne fuit pas les utilisateurs des autres magasins', function () {
    Livewire::actingAs($this->adminA)
        ->test(\App\Livewire\Team\ManageUsers::class)
        ->set('search', 'admin')
        ->assertSee('Admin A')
        ->assertDontSee('Admin B');
});

it('E-2 : une photo de pack d un autre magasin ne peut pas etre supprimee', function () {
    $packA = Pack::create(['store_id' => $this->storeA->id, 'name' => 'Pack A', 'reference' => 'PACK-A', 'pricing_mode' => 'fixed', 'pack_price' => 500, 'status' => 'active']);

    expect(fn () => Livewire::actingAs($this->adminA)
        ->test(\App\Livewire\Packs\PackForm::class, ['pack' => $packA])
        ->call('removeExistingPhoto', $this->packImageB->id))
        ->toThrow(ModelNotFoundException::class);

    expect(PackImage::withoutGlobalScopes()->whereKey($this->packImageB->id)->exists())->toBeTrue();
});

it('E-1 : un caissier ne peut pas cloturer une location', function () {
    $caissier = User::create(['store_id' => $this->storeA->id, 'name' => 'Caissier', 'email' => 'caisse@test.com', 'password' => 'password', 'is_active' => true]);
    $caissier->assignRole('cashier');

    $customer = Customer::create(['store_id' => $this->storeA->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550']);
    $rental = Rental::create([
        'store_id' => $this->storeA->id, 'customer_id' => $customer->id, 'user_id' => $this->adminA->id,
        'reference' => 'LOC-SEC-1', 'start_date' => now(), 'end_date' => now()->addDay(),
        'status' => 'active', 'subtotal' => 1000, 'total' => 1000,
    ]);

    Livewire::actingAs($caissier)
        ->test(\App\Livewire\Rentals\RentalShow::class, ['rental' => $rental])
        ->call('complete')
        ->assertForbidden();

    // Le caissier encaisse en revanche sans difficulte (payments.create).
    Livewire::actingAs($caissier)
        ->test(\App\Livewire\Rentals\RentalShow::class, ['rental' => $rental])
        ->set('paid_amount', 500)
        ->set('payment_method', 'cash')
        ->call('recordPayment')
        ->assertOk();

    expect($rental->refresh()->paid_amount)->toBe(500);
});

it('E-1 : un employe ne peut pas changer le statut d un article', function () {
    $employe = User::create(['store_id' => $this->storeA->id, 'name' => 'Employe 2', 'email' => 'emp2@test.com', 'password' => 'password', 'is_active' => true]);
    $employe->assignRole('employee');

    $product = Product::create(['store_id' => $this->storeA->id, 'name' => 'Costume A', 'reference' => 'A-1', 'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 2, 'status' => 'available']);

    Livewire::actingAs($employe)
        ->test(\App\Livewire\Products\ProductShow::class, ['product' => $product])
        ->call('changeStatus', 'cleaning')
        ->assertForbidden();

    expect($product->refresh()->status)->toBe('available');
});

it('E-3 : une preuve de paiement n est pas accessible depuis un autre magasin', function () {
    StoreContext::set($this->storeB->id);
    $customerB = Customer::create(['store_id' => $this->storeB->id, 'first_name' => 'Sara', 'last_name' => 'B', 'phone' => '0660']);
    $rentalB = Rental::create([
        'store_id' => $this->storeB->id, 'customer_id' => $customerB->id, 'user_id' => $this->adminB->id,
        'reference' => 'LOC-SEC-B', 'start_date' => now(), 'end_date' => now()->addDay(),
        'status' => 'active', 'subtotal' => 1000, 'total' => 1000,
    ]);
    $paymentB = Payment::create([
        'store_id' => $this->storeB->id, 'rental_id' => $rentalB->id, 'user_id' => $this->adminB->id,
        'reference' => 'PAY-B-1', 'amount' => 500, 'method' => 'cash', 'type' => 'payment',
        'date' => now()->toDateString(), 'proof_image_paths' => ['payments/'.$this->storeB->id.'/preuve.webp'],
    ]);

    StoreContext::set($this->storeA->id);

    $this->actingAs($this->adminA)
        ->get(route('files.payment', ['payment' => $paymentB->id, 'index' => 0]))
        ->assertNotFound();
});

it('M-1 : la reservation engage la disponibilite sans toucher au parc', function () {
    $customer = Customer::create(['store_id' => $this->storeA->id, 'first_name' => 'Nadia', 'last_name' => 'K', 'phone' => '0770']);
    $product = Product::create(['store_id' => $this->storeA->id, 'name' => 'Robe', 'reference' => 'A-2', 'rental_price' => 2000, 'caution_price' => 0, 'quantity' => 1, 'status' => 'available']);

    $rental = Rental::create([
        'store_id' => $this->storeA->id, 'customer_id' => $customer->id, 'user_id' => $this->adminA->id,
        'reference' => 'LOC-SEC-2', 'start_date' => now()->addDay(), 'end_date' => now()->addDays(3),
        'status' => 'reserved', 'subtotal' => 2000, 'total' => 2000,
    ]);
    RentalItem::create(['store_id' => $this->storeA->id, 'rental_id' => $rental->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 2000, 'line_total' => 2000]);

    expect($product->refresh()->quantity)->toBe(1);
    expect($product->freeBetween(now()->addDay()->toDateString(), now()->addDays(3)->toDateString()))->toBe(0);
    expect($product->freeBetween(now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString()))->toBe(1);
});

it('M-3 : un abonnement expire bloque les parametres du magasin', function () {
    $subscription = \App\Models\Subscription::where('store_id', $this->storeA->id)->firstOrFail();
    $subscription->update(['status' => 'active', 'ends_at' => now()->subMonth(), 'trial_ends_at' => null]);

    $this->actingAs($this->adminA)
        ->get(route('settings.index'))
        ->assertRedirect(route('subscription.index'));

    // Les pages de compte restent accessibles.
    $this->actingAs($this->adminA)
        ->get(route('settings.profile'))
        ->assertOk();
});

it('M-5 : un plan retire du catalogue ne peut plus etre souscrit', function () {
    $plan = \App\Models\Plan::where('slug', 'basic')->firstOrFail();
    $plan->update(['is_active' => false]);

    $this->actingAs($this->adminA)
        ->post(route('subscription.subscribe', $plan))
        ->assertNotFound();
});

it('le super admin travaille sur un seul magasin a la fois dans l espace magasin', function () {
    $superAdmin = User::where('is_super_admin', true)->firstOrFail();

    StoreContext::set($this->storeA->id);
    Product::create(['store_id' => $this->storeA->id, 'name' => 'Costume A', 'reference' => 'A-9', 'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 1, 'status' => 'available']);

    // Selection du magasin A via le selecteur de la barre laterale.
    $this->actingAs($superAdmin)
        ->post(route('store.context.switch'), ['store_id' => $this->storeA->id])
        ->assertRedirect();

    $this->actingAs($superAdmin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Costume A')
        ->assertDontSee('Costume B');

    // Bascule sur le magasin B : les articles de A disparaissent.
    $this->actingAs($superAdmin)
        ->post(route('store.context.switch'), ['store_id' => $this->storeB->id])
        ->assertRedirect();

    $this->actingAs($superAdmin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Costume B')
        ->assertDontSee('Costume A');
});

it('un utilisateur de magasin ne peut pas basculer le contexte', function () {
    $this->actingAs($this->adminA)
        ->post(route('store.context.switch'), ['store_id' => $this->storeB->id])
        ->assertForbidden();
});

it('un utilisateur sans magasin ne voit rien plutot que tout', function () {
    StoreContext::set($this->storeB->id);
    Product::create(['store_id' => $this->storeB->id, 'name' => 'Costume B2', 'reference' => 'B-2', 'rental_price' => 1000, 'caution_price' => 0, 'quantity' => 1, 'status' => 'available']);
    StoreContext::set(null);

    // Compte orphelin : store_id absent, mais pas super admin.
    $orphelin = User::create(['name' => 'Orphelin', 'email' => 'orphelin@test.com', 'password' => 'password', 'is_active' => true]);
    $orphelin->assignRole('admin');

    $this->actingAs($orphelin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertDontSee('Costume B')
        ->assertSee('rattaché à aucun magasin');

    // Le scope bloque aussi les requetes directes.
    StoreContext::restrict(null);
    expect(Product::count())->toBe(0);

    StoreContext::set(null);
    expect(Product::count())->toBeGreaterThan(0);
});

it('le contexte magasin est pose apres la session et avant le model binding', function () {
    // Invariant de sécurité : avant StartSession, $request->user() est vide, le
    // contexte reste nul et le scope tenant se désactive — toutes les données de
    // tous les magasins deviennent visibles. Après SubstituteBindings, la
    // résolution des modèles de route échapperait au scope.
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('products.index');
    $sorted = array_values(app('router')->gatherRouteMiddleware($route));

    $position = fn (string $class) => array_search($class, $sorted, true);

    $session = $position(\Illuminate\Session\Middleware\StartSession::class);
    $context = $position(\App\Http\Middleware\SetStoreContext::class);
    $bindings = $position(\Illuminate\Routing\Middleware\SubstituteBindings::class);

    expect($session)->not->toBeFalse()
        ->and($context)->not->toBeFalse()
        ->and($bindings)->not->toBeFalse();

    expect($session)->toBeLessThan($context);
    expect($context)->toBeLessThan($bindings);
});
