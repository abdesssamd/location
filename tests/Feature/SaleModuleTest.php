<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StockMovement;
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

    $this->customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $this->product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume Bleu', 'reference' => 'ART-VEN-1', 'rental_price' => 3000, 'sale_price' => 8000, 'caution_price' => 6000, 'quantity' => 5, 'status' => 'available']);
});

it('affiche la page de vente rapide', function () {
    $this->actingAs($this->user)->get(route('sales.create'))->assertOk();
});

it('cree une vente qui decremente reellement le parc, pas seulement l engagement', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $this->product->id)
        ->set('payment_method', 'cash')
        ->call('checkout');

    $sale = Sale::where('store_id', $this->store->id)->firstOrFail();
    expect($sale->status)->toBe(Sale::STATUS_COMPLETED);
    expect($sale->items()->count())->toBe(1);
    expect($sale->items()->first()->unit_price)->toBe(8000);
    expect($sale->total)->toBe(8000);
    expect($sale->paid_amount)->toBe(8000);

    // Contrairement a une location, la vente retire l'unite du parc pour de bon.
    expect($this->product->refresh()->quantity)->toBe(4);

    $movement = StockMovement::where('product_id', $this->product->id)->where('type', 'sale')->first();
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(-1);

    $payment = Payment::where('sale_id', $sale->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->rental_id)->toBeNull();
    expect($payment->amount)->toBe(8000);
});

it('utilise le prix de location si aucun prix de vente n est defini', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Chaussures', 'reference' => 'ART-VEN-2', 'rental_price' => 1500, 'caution_price' => 3000, 'quantity' => 2, 'status' => 'available']);

    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $product->id);

    expect($component->get('items')[0]['unit_price'])->toBe(1500);
});

it('refuse de vendre plus que le stock disponible', function () {
    $component = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $this->product->id)
        ->call('updateQuantity', 0, 999);

    expect($component->get('items')[0]['quantity'])->toBe(5);
});

it('applique une remise sur le total de la vente', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $this->product->id)
        ->set('discount', 1000)
        ->call('checkout');

    $sale = Sale::where('store_id', $this->store->id)->firstOrFail();
    expect($sale->subtotal)->toBe(8000);
    expect($sale->discount)->toBe(1000);
    expect($sale->total)->toBe(7000);
});

it('cree un nouveau client depuis le point de vente', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->set('new_first_name', 'Sara')
        ->set('new_last_name', 'B')
        ->set('new_phone', '0660')
        ->call('createCustomer');

    $customer = Customer::where('phone', '0660')->firstOrFail();
    expect($customer->store_id)->toBe($this->store->id);
});

it('annule une vente et restitue le stock', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $this->product->id)
        ->call('checkout');

    $sale = Sale::where('store_id', $this->store->id)->firstOrFail();
    expect($this->product->refresh()->quantity)->toBe(4);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SaleShow::class, ['sale' => $sale])
        ->call('cancel');

    expect($sale->refresh()->status)->toBe(Sale::STATUS_CANCELLED);
    expect($this->product->refresh()->quantity)->toBe(5);
});

it('un employe sans permission sales.create ne peut pas vendre', function () {
    $employe = User::create(['store_id' => $this->store->id, 'name' => 'Employe', 'email' => 'emp-vente@test.com', 'password' => 'password', 'is_active' => true]);
    $employe->assignRole('employee');

    $this->actingAs($employe)->get(route('sales.create'))->assertForbidden();
});

it('un magasin ne voit pas les ventes d un autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre', 'slug' => 'autre-vente', 'token' => 'tok-vente', 'status' => 'active']);
    StoreContext::set($otherStore->id);
    $otherProduct = Product::create(['store_id' => $otherStore->id, 'name' => 'Robe Autre', 'reference' => 'ART-VEN-AUTRE', 'rental_price' => 1000, 'sale_price' => 2000, 'caution_price' => 0, 'quantity' => 1, 'status' => 'available']);
    StoreContext::set($this->store->id);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $otherProduct->id);

    // Le produit d'un autre magasin n'est pas resolvable : Product::findOrFail echoue.
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('la liste des ventes reste isolee par magasin', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $this->product->id)
        ->call('checkout');

    $otherStore = Store::create(['name' => 'Autre B', 'slug' => 'autre-vente-b', 'token' => 'tok-vente-b', 'status' => 'active']);
    \App\Services\SubscriptionService::createSubscription($otherStore, \App\Models\Plan::where('slug', 'pro')->firstOrFail(), \App\Models\Subscription::STATUS_ACTIVE);
    \App\Models\StoreToken::issue($otherStore->id);
    $otherUser = User::create(['store_id' => $otherStore->id, 'name' => 'U2', 'email' => 'u2-vente@test.com', 'password' => 'password', 'is_active' => true]);
    $otherUser->assignRole('admin');

    $this->actingAs($otherUser)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertDontSee('ART-VEN-1');
});

it('separe le chiffre d affaires vente de celui de la location dans les rapports', function () {
    $rental = \App\Models\Rental::create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-RAP-1', 'start_date' => now(), 'end_date' => now()->addDays(2), 'status' => 'active', 'subtotal' => 3000, 'total' => 3000]);
    Payment::create(['store_id' => $this->store->id, 'rental_id' => $rental->id, 'user_id' => $this->user->id, 'reference' => 'PAY-RAP-1', 'amount' => 3000, 'method' => 'cash', 'type' => 'payment', 'date' => now()->toDateString()]);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Sales\SalePos::class)
        ->call('addProduct', $this->product->id)
        ->call('checkout');

    $component = Livewire::actingAs($this->user)->test(\App\Livewire\Reports\Reports::class);

    expect($component->viewData('revenue'))->toBe(3000);
    expect($component->viewData('saleRevenue'))->toBe(8000);
    expect($component->viewData('rentalCount'))->toBe(1);
    expect($component->viewData('saleCount'))->toBe(1);
});
