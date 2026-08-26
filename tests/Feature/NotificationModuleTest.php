<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\Store;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\UpcomingReturnNotification;
use App\Services\NotificationService;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    StoreContext::set($this->store->id);
});

it('notifie le manager quand le stock devient bas', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-000030', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 2, 'status' => 'available']);

    NotificationService::notifyLowStock($product);

    $notification = $this->user->notifications()->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe(LowStockNotification::class);
});

it('ne notifie pas si le stock est suffisant', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-000031', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 10, 'status' => 'available']);

    NotificationService::notifyLowStock($product);

    expect($this->user->notifications()->count())->toBe(0);
});

it('notifie un retour imminent', function () {
    $customer = Customer::create(['store_id' => $this->store->id, 'first_name' => 'Ali', 'last_name' => 'Cherif', 'phone' => '0550 11 22 33']);
    $rental = Rental::create(['store_id' => $this->store->id, 'customer_id' => $customer->id, 'user_id' => $this->user->id, 'reference' => 'LOC-2026-0001', 'start_date' => now()->subDay(), 'end_date' => now(), 'status' => 'active', 'subtotal' => 100, 'total' => 100]);

    NotificationService::notifyUpcomingReturn($rental);

    $notification = $this->user->notifications()->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe(UpcomingReturnNotification::class);
});

it('marque les notifications comme lues', function () {
    $product = Product::create(['store_id' => $this->store->id, 'name' => 'Costume', 'reference' => 'ART-000032', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);
    NotificationService::notifyLowStock($product);
    expect($this->user->unreadNotifications()->count())->toBeGreaterThan(0);

    $this->user->unreadNotifications->markAsRead();

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('le scan rapide trouve un article par reference', function () {
    Product::create(['store_id' => $this->store->id, 'name' => 'Smoking', 'reference' => 'SMK-999', 'rental_price' => 100, 'caution_price' => 100, 'quantity' => 1, 'status' => 'available']);

    \Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\Products\QuickScan::class)
        ->set('query', 'SMK-999')
        ->assertSee('Smoking');
});