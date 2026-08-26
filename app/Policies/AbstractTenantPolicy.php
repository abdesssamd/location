<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class AbstractTenantPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return null;
    }

    protected function owns(User $user, mixed $model): bool
    {
        if (! method_exists($model, 'getAttribute')) {
            return false;
        }

        return (int) $model->store_id === (int) $user->store_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->store_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->store_id !== null;
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }
}
