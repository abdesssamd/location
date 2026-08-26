<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetStoreContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $storeId = null;
        $store = null;

        if ($user && ! $user->is_super_admin) {
            $storeId = $user->store_id;
        }

        // Résolution par sous-domaine (ex: demo.louerpro.app)
        $subdomain = $this->resolveSubdomain($request);

        if ($subdomain) {
            $storeBySlug = Store::where('slug', $subdomain)->where('status', 'active')->first();

            if (! $storeBySlug) {
                abort(403, 'Magasin inconnu sur ce sous-domaine.');
            }

            if ($user && $user->is_super_admin) {
                $storeId = $storeBySlug->id;
            } elseif ($user && $user->store_id === $storeBySlug->id) {
                $storeId = $storeBySlug->id;
            } else {
                abort(403, 'Accès non autorisé à ce magasin.');
            }
        }

        StoreContext::set($storeId);

        return $next($request);
    }

    protected function resolveSubdomain(Request $request): ?string
    {
        // Désactivé tant qu'aucun domaine de base n'est configuré (ex: local)
        $baseDomain = strtolower((string) config('app.domain'));

        if ($baseDomain === '') {
            return null;
        }

        $host = strtolower($request->getHost());

        // Ignorer les adresses IP (127.0.0.1, etc.)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Le domaine de base lui-même ou www n'est pas un sous-domaine
        if ($host === $baseDomain || $host === 'www.'.$baseDomain) {
            return null;
        }

        if (! str_ends_with($host, '.'.$baseDomain)) {
            return null;
        }

        return explode('.', $host)[0] ?: null;
    }
}