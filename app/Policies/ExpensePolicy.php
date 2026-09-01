<?php

namespace App\Policies;

class ExpensePolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'expenses.view',
        'view' => 'expenses.view',
        'create' => 'expenses.create',
        'update' => 'expenses.create',
        'delete' => 'expenses.delete',
    ];
}
