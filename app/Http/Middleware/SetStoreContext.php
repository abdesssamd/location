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

        // Le super admin n'appartient à aucun magasin : dans l'espace magasin, il
        // travaille sur le magasin qu'il a choisi (ou le premier actif par défaut).
        // L'espace d'administration reste hors contexte pour garder des chiffres globaux.
        if ($user && $user->is_super_admin && ! $request->routeIs('admin.*')) {
            $storeId = $this->resolveAdminStore($request);
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

    /**
     * Magasin courant du super admin : celui choisi dans le sélecteur, sinon le
     * premier magasin actif. La valeur en session est revalidée à chaque requête.
     */
    protected function resolveAdminStore(Request $request): ?int
    {
        // Ce middleware s'exécute avant StartSession : on lit la session via le
        // conteneur (jamais $request->session()) et on n'y écrit pas — la
        // sélection est enregistrée par la route store.context.switch.
        $selected = (int) session()->get('admin_store_id');

        if ($selected && Store::whereKey($selected)->exists()) {
            return $selected;
        }

        return Store::where('status', 'active')->oldest()->value('id')
            ?? Store::oldest()->value('id');
    }

    protected function resolveSubdomain(Request $request): ?string
    {
        // Désactivé tant qu'aucun domaine de base n'est configuré (ex: local)
        $baseDomain = strtolower(trim((string) config('app.domain')));

        // APP_DOMAIN est souvent renseigné comme une URL complète : on normalise
        // plutôt que de désactiver silencieusement la résolution par sous-domaine.
        if ($baseDomain !== '') {
            $baseDomain = (string) (parse_url($baseDomain, PHP_URL_HOST) ?? $baseDomain);
            $baseDomain = trim(preg_replace('#^.*://#', '', $baseDomain), '/');
            $baseDomain = explode(':', $baseDomain)[0];
        }

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