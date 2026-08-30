<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inscription d'un magasin depuis la page publique.
 *
 * Selon les paramètres généraux, la demande est acceptée automatiquement
 * (magasin actif immédiatement, période de démonstration lancée) ou mise en
 * attente de validation par le super admin.
 */
class StoreRegistrar
{
    /**
     * @param  array{store_name: string, name: string, email: string, phone?: ?string, wilaya?: ?string, password: string}  $data
     * @return array{store: Store, user: User, token: ?StoreToken, approved: bool}
     */
    public static function register(array $data): array
    {
        $approved = PlatformSetting::autoApproves();

        return DB::transaction(function () use ($data, $approved) {
            $store = Store::create([
                'name' => $data['store_name'],
                'slug' => self::uniqueSlug($data['store_name']),
                'token' => '',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'wilaya' => $data['wilaya'] ?? null,
                'manager_name' => $data['name'],
                'currency' => 'DA',
                'status' => $approved ? 'active' : 'pending',
            ]);

            $user = User::create([
                'store_id' => $store->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => $approved,
                'locale' => 'fr',
            ]);
            $user->assignRole('admin');

            $token = null;

            if ($approved) {
                $token = self::activate($store, $user->id);
            }

            AuditLogger::log('store.self_registered', $store, null, [
                'mode' => $approved ? 'auto' : 'manual',
            ], $user, $store->id);

            return compact('store', 'user', 'token') + ['approved' => $approved];
        });
    }

    /**
     * Active un magasin en attente : token, compte administrateur et
     * période de démonstration configurée dans les paramètres généraux.
     */
    public static function activate(Store $store, ?int $userId = null): StoreToken
    {
        $store->update(['status' => 'active', 'suspended_at' => null]);
        $store->users()->update(['is_active' => true]);

        $token = SubscriptionService::generateToken($store, $userId);

        $existing = Subscription::where('store_id', $store->id)->exists();
        $plan = PlatformSetting::trialPlan();
        $trialDays = PlatformSetting::trialDays();

        if (! $existing && $plan) {
            SubscriptionService::createSubscription(
                $store,
                $plan,
                $trialDays > 0 ? Subscription::STATUS_TRIAL : Subscription::STATUS_ACTIVE,
                $trialDays,
                $userId
            );
        }

        return $token;
    }

    protected static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'magasin';
        $slug = $base;
        $i = 2;

        while (Store::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
