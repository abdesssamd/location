<?php

namespace App\Http\Middleware;

use App\Services\StoreContext;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque une route si le plan du magasin n'inclut pas la fonctionnalité
 * demandée (ex: gestion commerciale). Le super admin n'est jamais bloqué :
 * il doit pouvoir inspecter n'importe quel magasin sans être limité par
 * un plan qui ne lui appartient pas.
 */
class RequirePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $storeId = StoreContext::id();

        if (! $storeId || ! SubscriptionService::store($storeId)->hasFeature($feature)) {
            abort(403, "Cette fonctionnalité n'est pas incluse dans votre plan d'abonnement. Contactez votre administrateur pour la débloquer.");
        }

        return $next($request);
    }
}
