<?php

namespace App\Policies;

class PackPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'packs.view',
        'view' => 'packs.view',
        'create' => 'packs.create',
        'update' => 'packs.edit',
        'delete' => 'packs.archive',
    ];
}
