<?php

namespace App\Policies;

class CategoryPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'categories.view',
        'view' => 'categories.view',
        'create' => 'categories.manage',
        'update' => 'categories.manage',
        'delete' => 'categories.manage',
    ];
}
