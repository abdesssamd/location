<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Context;

class StoreContext
{
    /** Portée « magasin » : seules les données du magasin courant sont visibles. */
    public const SCOPE_TENANT = 'tenant';

    /** Portée « plateforme » : accès global, réservé au super admin et à la console. */
    public const SCOPE_GLOBAL = 'global';

    public static function set(?int $storeId, string $scope = self::SCOPE_GLOBAL): void
    {
        Context::add('store_id', $storeId);
        Context::add('store_scope', $storeId !== null ? self::SCOPE_TENANT : $scope);
        Context::add('store', $storeId ? Store::find($storeId) : null);
    }

    /**
     * Utilisateur rattaché à un magasin : la portée reste « magasin » même si
     * l'identifiant est absent, pour que le scope ne s'ouvre jamais par défaut.
     */
    public static function restrict(?int $storeId): void
    {
        self::set($storeId, self::SCOPE_TENANT);
    }

    public static function id(): ?int
    {
        return Context::get('store_id');
    }

    public static function scope(): ?string
    {
        return Context::get('store_scope');
    }

    public static function store(): ?Store
    {
        return Context::get('store');
    }
}
