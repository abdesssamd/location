<?php

namespace App\Policies;

use App\Models\User;

class RentalPolicy extends AbstractTenantPolicy
{
    protected array $permissions = [
        'viewAny' => 'rentals.view',
        'view' => 'rentals.view',
        'create' => 'rentals.create',
        'update' => 'rentals.create',
        'delete' => 'rentals.create',
        'checkout' => 'rentals.checkout',
        'complete' => 'rentals.return',
        'cancel' => 'reservations.cancel',
        'pay' => 'payments.create',
        'refund' => 'payments.refund',
    ];

    /** Sortie des articles : la réservation devient une location en cours. */
    public function checkout(User $user, mixed $rental): bool
    {
        return $this->allows($user, 'checkout', $rental);
    }

    /** Retour et clôture, avec contrôle des articles. */
    public function complete(User $user, mixed $rental): bool
    {
        return $this->allows($user, 'complete', $rental);
    }

    public function cancel(User $user, mixed $rental): bool
    {
        return $this->allows($user, 'cancel', $rental);
    }

    public function pay(User $user, mixed $rental): bool
    {
        return $this->allows($user, 'pay', $rental);
    }

    public function refund(User $user, mixed $rental): bool
    {
        return $this->allows($user, 'refund', $rental);
    }
}
