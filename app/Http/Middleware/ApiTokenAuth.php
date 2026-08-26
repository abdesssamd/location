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

        $storeToken = StoreToken::where('token', $token)
            ->where('status', StoreToken::STATUS_ACTIVE)
            ->with('store')
            ->first();

        if (! $storeToken || ! $storeToken->store) {
            abort(401, 'Token invalide ou révoqué.');
        }

        if ($storeToken->store->status !== 'active') {
            abort(403, 'Magasin suspendu.');
        }

        StoreContext::set($storeToken->store_id);

        return $next($request);
    }
}
