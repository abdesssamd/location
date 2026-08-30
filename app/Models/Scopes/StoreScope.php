<?php

namespace App\Models\Scopes;

use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = Context::get('store_id');

        if ($storeId !== null) {
            $builder->where($model->qualifyColumn('store_id'), $storeId);

            return;
        }

        // Aucun magasin en contexte : on ne montre rien plutôt que tout.
        // Sans cette barrière, un utilisateur dont le store_id est absent
        // verrait les données de l'ensemble des magasins.
        if (Context::get('store_scope') === StoreContext::SCOPE_TENANT) {
            $builder->whereRaw('1 = 0');
        }

        // Portée « plateforme » (super admin hors espace magasin, console,
        // jobs) : aucune restriction, comme auparavant.
    }
}
