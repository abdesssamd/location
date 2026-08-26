<?php

use App\Models\Audit;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::where('slug', 'demo')->firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    $this->superAdmin = User::where('is_super_admin', true)->firstOrFail();
});

it('resout le contexte par sous-domaine pour le super admin', function () {
    config(['app.domain' => 'localhost']);

    Store::create([
        'name' => 'Autre Magasin', 'slug' => 'autre', 'token' => 'tok-other', 'status' => 'active',
        'address' => 'Adresse', 'wilaya' => 'Alger', 'commune' => 'Centre', 'phone' => '0550 00 00 00',
        'email' => 'autre@ex.com', 'manager_name' => 'X', 'currency' => 'DZD', 'tax_rate' => 19,
    ]);

    Product::create(['store_id' => $this->store->id, 'name' => 'Article démo', 'reference' => 'DEMO-1', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 5, 'status' => 'available']);
    Product::create(['store_id' => Store::where('slug', 'autre')->first()->id, 'name' => 'Article autre', 'reference' => 'AUTRE-1', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 5, 'status' => 'available']);

    $this->actingAs($this->superAdmin)
        ->get('http://demo.localhost'.route('products.index', [], false))
        ->assertOk()
        ->assertSee('Article démo')
        ->assertDontSee('Article autre');
});

it('refuse un sous-domaine etranger pour un utilisateur de magasin', function () {
    config(['app.domain' => 'localhost']);

    $this->actingAs($this->user)
        ->get('http://inexistant.localhost'.route('products.index', [], false))
        ->assertForbidden();
});

it('ignore les adresses IP et le domaine local sans APP_DOMAIN', function () {
    // Sans APP_DOMAIN configuré (local), aucune résolution par sous-domaine
    $this->actingAs($this->user)
        ->get('http://127.0.0.1'.route('products.index', [], false))
        ->assertOk();

    // Le contexte retombe sur le magasin de l'utilisateur
    expect(StoreContext::id())->toBe($this->user->store_id);
});

it('le super admin consulte le journal d audit', function () {
    Audit::create([
        'store_id' => $this->store->id,
        'user_id' => $this->user->id,
        'action' => 'product.create',
        'auditable_type' => Product::class,
        'auditable_id' => 1,
    ]);

    $this->actingAs($this->superAdmin)
        ->get(route('admin.audits.index'))
        ->assertOk()
        ->assertSee('product.create');
});

it('exporte les donnees d un magasin en JSON', function () {
    $res = $this->actingAs($this->superAdmin)->get(route('admin.stores.export', $this->store));
    $res->assertOk();

    $data = json_decode($res->streamedContent(), true);
    expect($data['store']['slug'])->toBe('demo');
});