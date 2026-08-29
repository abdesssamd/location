<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CustomerList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = 'all'; // all | favorite

    public bool $showForm = false;
    public ?int $editingId = null;
    public bool $needsStore = false;
    public ?int $store_id = null;

    public function mount(): void
    {
        $this->needsStore = (bool) optional(auth()->user())->is_super_admin;

        if ($this->needsStore) {
            $this->store_id = (int) (session('admin_store_id', StoreContext::id() ?? (Store::where('status', 'active')->oldest()->value('id') ?? 0))) ?: null;
        }

        if (request()->routeIs('customers.create')) {
            $this->openCreate();
        } elseif (request()->routeIs('customers.edit')) {
            $this->openEdit((int) request()->route('customer'));
        }
    }

    public string $first_name = '';
    public string $last_name = '';
    public string $phone = '';
    public ?string $phone_secondary = '';
    public ?string $email = '';
    public string $cin = '';
    public string $address = '';
    public ?string $wilaya = '';
    public ?string $commune = '';
    public ?string $birth_date = '';
    public string $notes = '';
    public bool $favorite = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
    }

    public function openEdit(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->editingId = $customer->id;
        $this->first_name = $customer->first_name;
        $this->last_name = $customer->last_name;
        $this->phone = $customer->phone;
        $this->email = $customer->email ?? '';
        $this->cin = $customer->cin ?? '';
        $this->address = $customer->address ?? '';
        $this->wilaya = $customer->wilaya ?? '';
        $this->commune = $customer->commune ?? '';
        $this->birth_date = $customer->birth_date?->format('Y-m-d') ?? '';
        $this->notes = $customer->notes ?? '';
        $this->favorite = (bool) $customer->favorite;
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'first_name', 'last_name', 'phone', 'phone_secondary', 'email', 'cin', 'address', 'wilaya', 'commune', 'birth_date', 'notes', 'favorite']);
        $this->favorite = false;
    }

    public function save(): void
    {
        $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'cin' => ['nullable', 'string', 'max:30'],
            'wilaya' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
        ]);

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'phone_secondary' => $this->phone_secondary ?: null,
            'email' => $this->email ?: null,
            'cin' => $this->cin ?: null,
            'address' => $this->address ?: null,
            'wilaya' => $this->wilaya ?: null,
            'commune' => $this->commune ?: null,
            'birth_date' => $this->birth_date ?: null,
            'notes' => $this->notes ?: null,
            'favorite' => $this->favorite,
        ];

        if ($this->editingId) {
            $customer = Customer::findOrFail($this->editingId);
            $this->authorize('update', $customer);
            $old = $customer->getAttributes();
            $customer->update($data);
            AuditLogger::updated($customer, $old, 'customer.updated');
            $message = 'Client modifié.';
        } else {
            $this->authorize('create', Customer::class);
            $storeId = ((bool) optional(auth()->user())->is_super_admin && $this->store_id)
                ? $this->store_id
                : (StoreContext::id() ?? $this->store_id);

            if (! $storeId) {
                session()->flash('error', 'Veuillez sélectionner un magasin.');

                return;
            }

            $subscription = \App\Services\SubscriptionService::store($storeId);
            if (! $subscription->canCreateCustomer()) {
                session()->flash('error', $subscription->limitMessage('customer'));

                return;
            }

            $data['store_id'] = $storeId;
            $customer = Customer::create($data);
            AuditLogger::created($customer, 'customer.created');
            $message = 'Client créé.';
        }

        $this->showForm = false;
        $this->resetForm();
        session()->flash('status', $message);
    }

    public function toggleFavorite(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->authorize('update', $customer);
        $customer->update(['favorite' => ! $customer->favorite]);
    }

    public function deleteCustomer(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->authorize('delete', $customer);
        AuditLogger::deleted($customer, 'customer.deleted');
        $customer->delete();
        session()->flash('status', 'Client supprimé.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $customers = Customer::query()
            ->when(! $this->needsStore, fn ($q) => $q->where('store_id', StoreContext::id()))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('cin', 'like', '%'.$this->search.'%');
            }))
            ->when($this->filter === 'favorite', fn ($q) => $q->where('favorite', true))
            ->orderBy('last_name')
            ->paginate(12);

        return view('livewire.customers.customer-list', compact('customers'));
    }
}