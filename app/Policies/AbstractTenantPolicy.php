<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class AbstractTenantPolicy
{
    use HandlesAuthorization;

    /**
     * Correspondance ability -> permission Spatie.
     * Une ability absente de la table n'exige aucune permission particulière.
     *
     * @var array<string, string>
     */
    protected array $permissions = [];

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

    /**
     * Les routes ne protègent que la page ; chaque action d'un composant Livewire
     * est appelable individuellement. La permission doit donc être vérifiée ici.
     */
    protected function permits(User $user, string $ability): bool
    {
        $permission = $this->permissions[$ability] ?? null;

        return $permission === null || $user->can($permission);
    }

    protected function allows(User $user, string $ability, mixed $model = null): bool
    {
        if (! $this->permits($user, $ability)) {
            return false;
        }

        if ($model === null) {
            return $user->store_id !== null;
        }

        return $this->owns($user, $model);
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'viewAny');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->allows($user, 'view', $model);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->allows($user, 'update', $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->allows($user, 'delete', $model);
    }
}
