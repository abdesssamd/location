<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'support.view',
        'view' => 'support.view',
        'create' => 'support.create',
        'update' => 'support.create',
    ];

    /**
     * Repondre : le magasin proprietaire du ticket, ou le support.
     * Le before() de la classe parente laisse deja passer le super admin.
     */
    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $this->allows($user, 'update', $ticket);
    }
}
