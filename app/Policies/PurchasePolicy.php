<?php

namespace App\Policies;

use App\Models\User;

class PurchasePolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'purchases.view',
        'view' => 'purchases.view',
        'create' => 'purchases.create',
        'update' => 'purchases.create',
        'delete' => 'purchases.cancel',
        'cancel' => 'purchases.cancel',
    ];

    public function cancel(User $user, mixed $purchase): bool
    {
        return $this->allows($user, 'cancel', $purchase);
    }
}
