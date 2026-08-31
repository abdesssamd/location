<?php

namespace App\Policies;

use App\Models\User;

class SalePolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'sales.view',
        'view' => 'sales.view',
        'create' => 'sales.create',
        'update' => 'sales.create',
        'delete' => 'sales.cancel',
        'cancel' => 'sales.cancel',
    ];

    public function cancel(User $user, mixed $sale): bool
    {
        return $this->allows($user, 'cancel', $sale);
    }
}
