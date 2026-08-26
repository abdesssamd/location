<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedSuperAdmin();
        $this->seedPlans();
        $this->seedDemoStore();
    }

    protected function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'BASIC', 'slug' => 'basic', 'price' => 1500, 'billing_period' => 'monthly',
                'description' => 'Pour démarrer avec l\'essentiel.',
                'max_users' => 1, 'max_products' => 100, 'max_customers' => 50, 'max_storage_mb' => 1024,
                'features' => ['locations', 'contracts_pdf', 'statistics', 'packs'],
                'sort_order' => 1, 'is_popular' => false,
            ],
            [
                'name' => 'PRO', 'slug' => 'pro', 'price' => 3000, 'billing_period' => 'monthly',
                'description' => 'Le choix des magasins en croissance.',
                'max_users' => 5, 'max_products' => null, 'max_customers' => null, 'max_storage_mb' => 5120,
                'features' => ['locations', 'contracts_pdf', 'qr_code', 'statistics', 'notifications', 'packs', 'multi_users', 'export_excel'],
                'sort_order' => 2, 'is_popular' => true,
            ],
            [
                'name' => 'PREMIUM', 'slug' => 'premium', 'price' => 5000, 'billing_period' => 'monthly',
                'description' => 'Tout illimité, pour les professionnels.',
                'max_users' => null, 'max_products' => null, 'max_customers' => null, 'max_storage_mb' => 20480,
                'features' => ['locations', 'contracts_pdf', 'qr_code', 'statistics', 'advanced_statistics', 'notifications', 'packs', 'multi_users', 'export_excel', 'api'],
                'sort_order' => 3, 'is_popular' => false,
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            // Produits / Stock
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'packs.view', 'packs.create', 'packs.edit', 'packs.archive',
            'categories.view', 'categories.manage',
            'stock.manage',
            // Clients
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            // Locations / Réservations
            'rentals.view', 'rentals.create', 'rentals.return', 'rentals.checkout',
            'reservations.view', 'reservations.create', 'reservations.cancel',
            // Contrats
            'contracts.view', 'contracts.create', 'contracts.pdf', 'contracts.delete',
            // Paiements
            'payments.view', 'payments.create', 'payments.refund',
            // Rapports
            'reports.view',
            // Paramètres & équipe
            'settings.manage',
            'users.manage',
            'store.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function seedRoles(): void
    {
        $roles = [
            'super_admin' => Permission::all()->pluck('name')->all(),
            'admin' => Permission::all()->pluck('name')->all(),
            'manager' => [
                'products.view', 'products.create', 'products.edit', 'products.delete',
                'packs.view', 'packs.create', 'packs.edit', 'packs.archive',
                'categories.view', 'categories.manage', 'stock.manage',
                'customers.view', 'customers.create', 'customers.edit',
                'rentals.view', 'rentals.create', 'rentals.return', 'rentals.checkout',
                'reservations.view', 'reservations.create', 'reservations.cancel',
                'contracts.view', 'contracts.create', 'contracts.pdf',
                'payments.view', 'payments.create', 'payments.refund',
                'reports.view', 'settings.manage', 'users.manage',
            ],
            'cashier' => [
                'customers.view', 'customers.create', 'customers.edit',
                'packs.view', 'packs.create',
                'rentals.view', 'rentals.create',
                'reservations.view', 'reservations.create', 'reservations.cancel',
                'contracts.view', 'contracts.create', 'contracts.pdf',
                'payments.view', 'payments.create', 'payments.refund',
            ],
            'storekeeper' => [
                'products.view', 'products.create', 'products.edit', 'stock.manage',
                'packs.view',
                'categories.view', 'rentals.return', 'rentals.checkout',
                'rentals.view', 'contracts.view',
            ],
            'employee' => [
                'products.view', 'packs.view', 'customers.view',
                'rentals.view', 'reservations.view', 'contracts.view', 'payments.view',
            ],
        ];

        foreach ($roles as $name => $perms) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }

    protected function seedSuperAdmin(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@louerpro.app');

        if (User::where('email', $email)->exists()) {
            return;
        }

        $user = User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');
    }

    protected function seedDemoStore(): void
    {
        if (Store::where('slug', 'demo')->exists()) {
            return;
        }

        $store = Store::create([
            'name' => 'LouerPro Démo',
            'slug' => 'demo',
            'token' => \App\Services\ReferenceGenerator::storeToken(),
            'address' => '12 rue des Artisans',
            'wilaya' => 'Alger',
            'commune' => 'Alger Centre',
            'phone' => '0550 00 00 00',
            'email' => 'contact@demo.louerpro.app',
            'manager_name' => 'Super Admin',
            'currency' => 'DA',
            'contract_prefix' => 'CTR',
            'status' => 'active',
        ]);

        \App\Models\StoreToken::create([
            'store_id' => $store->id,
            'token' => $store->token,
            'status' => 'active',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'gestion@demo.louerpro.app'],
            [
                'store_id' => $store->id,
                'name' => 'Gestionnaire Démo',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $admin->assignRole('admin');

        // Abonnement démo : PRO actif 30 jours
        $pro = \App\Models\Plan::where('slug', 'pro')->first();
        if ($pro) {
            \App\Services\SubscriptionService::createSubscription($store, $pro, \App\Models\Subscription::STATUS_ACTIVE, 0);
        }
    }
}