<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SupplierManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $notes = '';

    public string $search = '';
    public bool $showForm = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Supplier::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Supplier::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $supplierId): void
    {
        $supplier = Supplier::findOrFail($supplierId);
        $this->authorize('update', $supplier);

        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->phone = (string) $supplier->phone;
        $this->email = (string) $supplier->email;
        $this->address = (string) $supplier->address;
        $this->notes = (string) $supplier->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $storeId = StoreContext::id();
        abort_if($storeId === null, 403, 'Aucun magasin associé à votre compte.');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            $this->authorize('update', $supplier);

            $old = $supplier->getAttributes();
            $supplier->update([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
                'address' => $this->address ?: null,
                'notes' => $this->notes ?: null,
            ]);
            AuditLogger::updated($supplier, $old, 'supplier.updated');
            session()->flash('status', 'Fournisseur mis à jour.');
        } else {
            $this->authorize('create', Supplier::class);

            $supplier = Supplier::create([
                'store_id' => $storeId,
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
                'address' => $this->address ?: null,
                'notes' => $this->notes ?: null,
            ]);
            AuditLogger::created($supplier, 'supplier.created');
            session()->flash('status', 'Fournisseur « '.$supplier->name.' » créé.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleActive(int $supplierId): void
    {
        $supplier = Supplier::findOrFail($supplierId);
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => ! $supplier->is_active]);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'email', 'address', 'notes']);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        $suppliers = Supplier::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%');
            }))
            ->withCount('purchases')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.suppliers.supplier-manager', compact('suppliers'));
    }
}
