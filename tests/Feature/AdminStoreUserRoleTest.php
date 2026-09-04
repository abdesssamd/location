<?php

use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    Notification::fake();

    $this->superAdmin = User::where('is_super_admin', true)->firstOrFail();

    $this->store = Store::create(['name' => 'Cible', 'slug' => 'cible-role', 'token' => 'tok-role', 'status' => 'active']);
    SubscriptionService::createSubscription($this->store, Plan::where('slug', 'pro')->firstOrFail(), Subscription::STATUS_ACTIVE, 0);
});

it('cree un utilisateur de magasin avec le role choisi', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.stores.admins.store', $this->store), [
            'name' => 'Caissier Test',
            'email' => 'caissier-role@test.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'cashier',
        ])
        ->assertRedirect(route('admin.stores.show', $this->store));

    $user = User::where('email', 'caissier-role@test.com')->firstOrFail();

    expect($user->store_id)->toBe($this->store->id);
    expect($user->hasRole('cashier'))->toBeTrue();
    expect($user->hasRole('admin'))->toBeFalse();
});

it('cree encore un administrateur quand ce role est choisi', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.stores.admins.store', $this->store), [
            'name' => 'Admin Test',
            'email' => 'admin-role@test.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'admin',
        ]);

    expect(User::where('email', 'admin-role@test.com')->firstOrFail()->hasRole('admin'))->toBeTrue();
});

it('accepte chacun des roles de magasin', function () {
    foreach (User::assignableRoles() as $index => $role) {
        $email = 'role-'.$role.'@test.com';

        $this->actingAs($this->superAdmin)
            ->post(route('admin.stores.admins.store', $this->store), [
                'name' => 'User '.$role,
                'email' => $email,
                'password' => 'motdepasse123',
                'password_confirmation' => 'motdepasse123',
                'role' => $role,
            ]);

        expect(User::where('email', $email)->firstOrFail()->hasRole($role))->toBeTrue();
    }
});

it('refuse d attribuer le role super_admin depuis la fiche magasin', function () {
    // super_admin donnerait acces aux donnees de tous les magasins.
    $this->actingAs($this->superAdmin)
        ->post(route('admin.stores.admins.store', $this->store), [
            'name' => 'Pirate',
            'email' => 'pirate-role@test.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'super_admin',
        ])
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'pirate-role@test.com')->exists())->toBeFalse();
});

it('refuse un role inexistant', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.stores.admins.store', $this->store), [
            'name' => 'Inconnu',
            'email' => 'inconnu-role@test.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'directeur_general',
        ])
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'inconnu-role@test.com')->exists())->toBeFalse();
});

it('cree un administrateur quand aucun role n est precise', function () {
    // Compatibilite : le formulaire d'origine n'envoyait pas de role.
    $this->actingAs($this->superAdmin)
        ->post(route('admin.stores.admins.store', $this->store), [
            'name' => 'Sans Role',
            'email' => 'sansrole@test.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])
        ->assertSessionHas('status');

    expect(User::where('email', 'sansrole@test.com')->firstOrFail()->hasRole('admin'))->toBeTrue();
});

it('un utilisateur de magasin ne peut pas creer de compte via l espace admin', function () {
    $admin = User::create([
        'store_id' => $this->store->id, 'name' => 'Admin Magasin', 'email' => 'adminmag-role@test.com',
        'password' => 'password', 'is_active' => true,
    ]);
    $admin->assignRole('admin');

    // Le groupe de routes admin est protege par role:super_admin, qui redirige
    // au lieu de renvoyer 403 : ce qui compte est qu'aucun compte ne soit cree.
    $this->actingAs($admin)
        ->post(route('admin.stores.admins.store', $this->store), [
            'name' => 'Intrus',
            'email' => 'intrus-role@test.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role' => 'admin',
        ])
        ->assertRedirect();

    expect(User::where('email', 'intrus-role@test.com')->exists())->toBeFalse();
});
