<?php

namespace App\Policies;

class CustomerPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'customers.view',
        'view' => 'customers.view',
        'create' => 'customers.create',
        'update' => 'customers.edit',
        'delete' => 'customers.delete',
    ];
}
