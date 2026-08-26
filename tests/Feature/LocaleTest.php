<?php

use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    StoreContext::set($this->store->id);
});

it('bascule la langue en arabe et persistante', function () {
    $this->actingAs($this->user)
        ->get(route('locale.switch', 'ar'))
        ->assertRedirect();

    expect(session('locale'))->toBe('ar');
    expect($this->user->fresh()->locale)->toBe('ar');
});

it('applique dir rtl quand la locale est arabe', function () {
    $this->actingAs($this->user)
        ->get(route('locale.switch', 'ar'));

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('dir="rtl"', false);
});

it('refuse une langue inconnue', function () {
    $this->actingAs($this->user)
        ->get(route('locale.switch', 'xx'))
        ->assertNotFound();
});