<?php

namespace App\Policies;

use App\Models\User;

class PaymentPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'payments.view',
        'view' => 'payments.view',
        'create' => 'payments.create',
        'update' => 'payments.create',
        'delete' => 'payments.refund',
        'refund' => 'payments.refund',
    ];

    public function refund(User $user, mixed $payment = null): bool
    {
        return $this->allows($user, 'refund', $payment);
    }
}
