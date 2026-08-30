<?php

namespace App\Http\Middleware;

use App\Models\StoreToken;
use App\Services\StoreContext;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle($request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-Store-Token');

        if (! $token) {
            abort(401, 'Token du magasin requis.');
        }

        // Comparaison sur l'empreinte : la base ne contient pas le token en clair.
        $storeToken = StoreToken::findActiveByPlainText($token);

        if (! $storeToken || ! $storeToken->store) {
            abort(401, 'Token invalide ou révoqué.');
        }

        if ($storeToken->store->status !== 'active') {
            abort(403, 'Magasin suspendu.');
        }

        // Traçabilité : une fuite de token devient détectable (dernière IP, dernier usage).
        $storeToken->forceFill([
            'last_used_at' => now(),
            'last_ip' => $request->ip(),
        ])->saveQuietly();

        StoreContext::set($storeToken->store_id);

        return $next($request);
    }
}
