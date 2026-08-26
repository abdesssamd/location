<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Routes exemptées (pages d'abonnement, profil, déconnexion, langue).
     */
    protected array $except = [
        'subscription.*',
        'plans',
        'settings.*',
        'profile.*',
        'password.*',
        'logout',
        'locale.*',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Invités et super admin non concernés
        if (! $user || $user->is_super_admin) {
            return $next($request);
        }

        $storeId = $user->store_id;

        if (! $storeId) {
            return $next($request);
        }

        // Pages d'abonnement toujours accessibles
        if ($request->routeIs($this->except)) {
            return $next($request);
        }

        $store = Store::find($storeId);

        if (! $store || $store->status !== 'active') {
            return $this->block($request, 'Votre magasin est actuellement suspendu. Veuillez contacter l\'administrateur.');
        }

        // Token actif obligatoire
        if (! SubscriptionService::tokenIsValid($storeId)) {
            return $this->block($request, 'Token du magasin invalide ou désactivé. Veuillez contacter l\'administrateur.');
        }

        $service = SubscriptionService::store($storeId);

        if (! $service->subscription()) {
            return $this->block($request, 'Aucun abonnement actif pour votre magasin. Choisissez un plan pour continuer.');
        }

        // Abonnement expiré au-delà de la période de grâce
        if ($service->isExpired() && ! $service->inGrace()) {
            return $this->block($request, 'Votre abonnement a expiré. Veuillez renouveler votre abonnement pour continuer.');
        }

        // Période de grâce : accès autorisé mais alerte
        if ($service->inGrace()) {
            session()->flash('warning', 'Votre abonnement a expiré. Vous êtes en période de grâce jusqu\'au '.$service->graceEndsAt()?->format('d/m/Y').'. Veuillez renouveler votre abonnement.');
        } elseif (($threshold = $service->warningThreshold()) !== null) {
            session()->flash('warning', 'Votre abonnement expire dans '.$service->daysRemaining().' jour(s). Pensez à le renouveler.');
        }

        return $next($request);
    }

    protected function block(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            abort(402, $message);
        }

        return redirect()
            ->route('subscription.index')
            ->with('error', $message);
    }
}