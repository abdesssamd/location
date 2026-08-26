<?php

namespace App\Livewire\Team;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app')]
class ManageUsers extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $role = 'employee';
    public bool $isActive = true;

    protected $listeners = ['refreshTeam' => '$refresh'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->getRoleNames()->first() ?? 'employee';
        $this->isActive = (bool) $user->is_active;
        $this->password = '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->editingUserId],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,manager,cashier,storekeeper,employee'],
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->isActive,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $old = $user->getAttributes();
            $user->update($data);
            AuditLogger::log('team.updated', $user, $old, $user->getChanges());
        } else {
            $subscription = \App\Services\SubscriptionService::store(\App\Services\StoreContext::id());
            if (! $subscription->canCreateUser()) {
                session()->flash('error', $subscription->limitMessage('user'));

                return;
            }

            $data['store_id'] = \App\Services\StoreContext::id();
            $data['password'] = Hash::make($this->password);
            $user = User::create($data);
            AuditLogger::created($user, 'team.created');
        }

        $user->syncRoles([$this->role]);

        $this->closeForm();
        session()->flash('status', 'Utilisateur enregistré.');
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
        AuditLogger::log('team.active_toggled', $user);
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return;
        }

        AuditLogger::deleted($user, 'team.deleted');
        $user->delete();
        session()->flash('status', 'Utilisateur supprimé.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $users = User::query()
            ->when(\App\Services\StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')
                ->orWhere('phone', 'like', '%'.$this->search.'%'))
            ->with('roles')
            ->latest()
            ->paginate(10);

        $roles = Role::whereIn('name', ['admin', 'manager', 'cashier', 'storekeeper', 'employee'])->get();

        return view('livewire.team.manage-users', compact('users', 'roles'));
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->password = '';
        $this->role = 'employee';
        $this->isActive = true;
    }
}