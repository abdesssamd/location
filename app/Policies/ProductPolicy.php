<?php

namespace App\Policies;

use App\Models\User;

class ProductPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'products.view',
        'view' => 'products.view',
        'create' => 'products.create',
        'update' => 'products.edit',
        'delete' => 'products.delete',
        'changeStatus' => 'stock.manage',
    ];

    /** Changement d'état du stock (nettoyage, réparation, hors service…). */
    public function changeStatus(User $user, mixed $product): bool
    {
        return $this->allows($user, 'changeStatus', $product);
    }
}
