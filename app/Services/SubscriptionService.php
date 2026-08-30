<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public const GRACE_DAYS = 3;
    public const WARNING_DAYS = [7, 3];

    protected ?Subscription $subscription = null;

    public function __construct(protected ?int $storeId = null)
    {
        if ($storeId !== null) {
            $this->forStore($storeId);
        }
    }

    public static function store(?int $storeId = null): self
    {
        return new self($storeId ?? StoreContext::id());
    }

    public function forStore(int $storeId): self
    {
        $this->subscription = Subscription::where('store_id', $storeId)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'trial' THEN 1 WHEN 'expired' THEN 2 WHEN 'suspended' THEN 3 WHEN 'pending' THEN 4 ELSE 5 END")
            ->orderByDesc('ends_at')
            ->first();

        $this->storeId = $storeId;

        return $this;
    }

    public function subscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function plan(): ?Plan
    {
        return $this->subscription?->plan;
    }

    public function status(): string
    {
        if (! $this->subscription) {
            return 'none';
        }

        if (in_array($this->subscription->status, [Subscription::STATUS_CANCELLED, Subscription::STATUS_SUSPENDED, Subscription::STATUS_PENDING], true)) {
            return $this->subscription->status;
        }

        if ($this->endsAt() && $this->endsAt()->isPast()) {
            return Subscription::STATUS_EXPIRED;
        }

        return $this->subscription->status;
    }

    public function endsAt(): ?\Carbon\CarbonInterface
    {
        $sub = $this->subscription;

        if (! $sub) {
            return null;
        }

        if ($sub->status === Subscription::STATUS_TRIAL && $sub->trial_ends_at) {
            return $sub->trial_ends_at;
        }

        return $sub->ends_at;
    }

    public function isActive(): bool
    {
        return in_array($this->status(), [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL], true);
    }

    public function isExpired(): bool
    {
        return $this->status() === Subscription::STATUS_EXPIRED;
    }

    public function isSuspended(): bool
    {
        return $this->status() === Subscription::STATUS_SUSPENDED;
    }

    public function daysRemaining(): ?int
    {
        $ends = $this->endsAt();

        if (! $ends) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($ends->copy()->endOfDay(), false));
    }

    public function inGrace(): bool
    {
        if (! $this->isExpired()) {
            return false;
        }

        $ends = $this->endsAt();

        return $ends !== null && now()->diffInDays($ends->copy()->endOfDay(), false) >= -self::GRACE_DAYS;
    }

    public function graceEndsAt(): ?\Carbon\CarbonInterface
    {
        $ends = $this->endsAt();

        return $ends?->copy()->endOfDay()->addDays(self::GRACE_DAYS);
    }

    public function expiresSoon(?int $withinDays = null): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $days = $this->daysRemaining();

        return $days !== null && $days <= ($withinDays ?? min(self::WARNING_DAYS));
    }

    public function warningThreshold(): ?int
    {
        $days = $this->daysRemaining();

        if ($days === null) {
            return null;
        }

        // Le seuil le plus proche applicable (ex: 3 jours restants -> 3)
        foreach (array_reverse(self::WARNING_DAYS) as $threshold) {
            if ($days <= $threshold) {
                return $threshold;
            }
        }

        return null;
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->plan()?->features ?? [];

        return in_array($feature, (array) $features, true);
    }

    public function usage(): array
    {
        $storeId = $this->storeId ?? StoreContext::id();

        return [
            'products' => $storeId ? (int) Product::where('store_id', $storeId)->count() : 0,
            'customers' => $storeId ? (int) Customer::where('store_id', $storeId)->count() : 0,
            'users' => $storeId ? (int) User::where('store_id', $storeId)->count() : 0,
        ];
    }

    public function canCreate(string $resource): bool
    {
        $plan = $this->plan();

        if (! $plan) {
            return false;
        }

        $max = match ($resource) {
            'product' => $plan->max_products,
            'customer' => $plan->max_customers,
            'user' => $plan->max_users,
            default => null,
        };

        if ($max === null) {
            return true;
        }

        $usage = $this->usage();

        return $usage[match ($resource) {
            'product' => 'products',
            'customer' => 'customers',
            'user' => 'users',
        }] < $max;
    }

    public function canCreateProduct(): bool
    {
        return $this->canCreate('product');
    }

    public function canCreateCustomer(): bool
    {
        return $this->canCreate('customer');
    }

    public function canCreateUser(): bool
    {
        return $this->canCreate('user');
    }

    public function limitMessage(string $resource): string
    {
        $plan = $this->plan();

        $labels = [
            'product' => 'articles',
            'customer' => 'clients',
            'user' => 'utilisateurs',
        ];

        $max = match ($resource) {
            'product' => $plan?->max_products,
            'customer' => $plan?->max_customers,
            'user' => $plan?->max_users,
            default => null,
        };

        return sprintf(
            'Limite atteinte : votre plan %s autorise %s %s maximum. Passez à un plan supérieur pour en ajouter davantage.',
            $plan?->name ?? 'actuel',
            $max ?? 'un nombre illimité de',
            $labels[$resource] ?? $resource
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actions (renouvellement, changement de plan, tokens)
    |--------------------------------------------------------------------------
    */

    public static function createSubscription(Store $store, Plan $plan, string $status = Subscription::STATUS_TRIAL, int $trialDays = 14, ?int $userId = null): Subscription
    {
        return DB::transaction(function () use ($store, $plan, $status, $trialDays, $userId) {
            $subscription = Subscription::create([
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => now(),
                'ends_at' => $status === Subscription::STATUS_TRIAL ? now()->addDays($trialDays) : now()->addMonths($plan->months()),
                'trial_ends_at' => $status === Subscription::STATUS_TRIAL ? now()->addDays($trialDays) : null,
            ]);

            SubscriptionHistory::create([
                'store_id' => $store->id,
                'new_plan_id' => $plan->id,
                'action' => SubscriptionHistory::ACTION_CREATED,
                'reason' => 'Abonnement initial ('.$plan->name.')',
                'user_id' => $userId,
            ]);

            return $subscription;
        });
    }

    public static function renew(Store $store, Plan $plan, ?int $userId = null, string $reason = 'Renouvellement'): Subscription
    {
        return DB::transaction(function () use ($store, $plan, $userId, $reason) {
            $current = Subscription::where('store_id', $store->id)
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL, Subscription::STATUS_EXPIRED, Subscription::STATUS_SUSPENDED])
                ->orderByDesc('ends_at')
                ->first();

            // Ne pas perdre les jours restants : prolonger depuis l'expiration actuelle si future
            $base = ($current && $current->ends_at && $current->ends_at->isFuture()) ? $current->ends_at : now();

            if ($current) {
                $current->update([
                    'plan_id' => $plan->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'ends_at' => $base->copy()->addMonths($plan->months()),
                    'cancelled_at' => null,
                ]);
                $subscription = $current;
            } else {
                $subscription = Subscription::create([
                    'store_id' => $store->id,
                    'plan_id' => $plan->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($plan->months()),
                ]);
            }

            SubscriptionHistory::create([
                'store_id' => $store->id,
                'old_plan_id' => $current?->plan_id === $plan->id ? $plan->id : $current?->plan_id,
                'new_plan_id' => $plan->id,
                'action' => SubscriptionHistory::ACTION_RENEWED,
                'reason' => $reason,
                'amount' => $plan->price,
                'user_id' => $userId,
            ]);

            return $subscription;
        });
    }

    /**
     * Changement de plan avec vérification des limites.
     *
     * @return array{0: bool, 1: ?string} [succès, message d'erreur]
     */
    public static function changePlan(Store $store, Plan $newPlan, ?int $userId = null): array
    {
        $service = self::store($store->id);
        $usage = $service->usage();

        $checks = [
            'articles' => [$usage['products'], $newPlan->max_products],
            'clients' => [$usage['customers'], $newPlan->max_customers],
            'utilisateurs' => [$usage['users'], $newPlan->max_users],
        ];

        foreach ($checks as $label => [$count, $max]) {
            if ($max !== null && $count > $max) {
                return [false, sprintf(
                    'Impossible de passer à ce plan. Votre magasin possède %d %s alors que le plan %s en autorise seulement %d. Veuillez supprimer ou archiver des données.',
                    $count, $label, $newPlan->name, $max
                )];
            }
        }

        $current = Subscription::where('store_id', $store->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL, Subscription::STATUS_EXPIRED, Subscription::STATUS_SUSPENDED])
            ->orderByDesc('ends_at')
            ->first();

        if (! $current) {
            self::createSubscription($store, $newPlan, Subscription::STATUS_ACTIVE, 0, $userId);

            return [true, 'Abonnement '.$newPlan->name.' activé.'];
        }

        $oldPlanId = $current->plan_id;
        $current->update(['plan_id' => $newPlan->id, 'status' => Subscription::STATUS_ACTIVE]);

        SubscriptionHistory::create([
            'store_id' => $store->id,
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $newPlan->id,
            'action' => $newPlan->price >= ($current->plan?->price ?? 0) ? SubscriptionHistory::ACTION_UPGRADED : SubscriptionHistory::ACTION_DOWNGRADED,
            'reason' => 'Changement de plan vers '.$newPlan->name,
            'user_id' => $userId,
        ]);

        return [true, 'Plan changé vers '.$newPlan->name.'.'];
    }

    /*
    |--------------------------------------------------------------------------
    | Tokens
    |--------------------------------------------------------------------------
    */

    public static function generateToken(Store $store, ?int $userId = null): StoreToken
    {
        return DB::transaction(function () use ($store, $userId) {
            StoreToken::where('store_id', $store->id)
                ->where('status', StoreToken::STATUS_ACTIVE)
                ->update(['status' => StoreToken::STATUS_REVOKED, 'revoked_at' => now()]);

            $token = StoreToken::issue($store->id, $userId);

            // La fiche magasin n'affiche que l'aperçu masqué ; la valeur complète
            // n'est montrée qu'une fois, à la génération.
            $store->update(['token' => $token->token]);

            return $token;
        });
    }

    public static function tokenIsValid(int $storeId): bool
    {
        return StoreToken::where('store_id', $storeId)
            ->where('status', StoreToken::STATUS_ACTIVE)
            ->exists();
    }
}