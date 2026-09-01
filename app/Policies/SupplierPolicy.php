<?php

namespace App\Policies;

class SupplierPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'suppliers.view',
        'view' => 'suppliers.view',
        'create' => 'suppliers.manage',
        'update' => 'suppliers.manage',
        'delete' => 'suppliers.manage',
    ];
}
