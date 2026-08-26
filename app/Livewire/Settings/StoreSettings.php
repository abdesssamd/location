<?php

namespace App\Livewire\Settings;

use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class StoreSettings extends Component
{
    use WithFileUploads;

    public ?Store $store = null;
    public $selectedStoreId = null;

    public string $name = '';
    public string $address = '';
    public string $wilaya = '';
    public string $commune = '';
    public string $phone = '';
    public string $phone_secondary = '';
    public string $email = '';
    public string $color = '#1e3a5f';
    public string $currency = 'DA';
    public bool $tax_enabled = false;
    public string $tax_rate = '0';
    public string $late_fee_per_day = '0';
    public string $contract_prefix = 'CTR';
    public string $rental_conditions = '';

    public $logo;

    public function mount(): void
    {
        $storeId = StoreContext::id();

        if (Auth::user()?->is_super_admin) {
            $storeId = (int) (session('admin_store_id', $storeId ?? Store::query()->oldest()->value('id') ?? 0));
            if ($storeId) {
                StoreContext::set($storeId);
            }
        }

        if ($storeId) {
            $this->store = Store::findOrFail($storeId);
            $this->hydrateFromStore();
        }

        $this->selectedStoreId = $storeId ?: null;
    }

    public function selectStore(): void
    {
        if (! Auth::user()?->is_super_admin) {
            return;
        }

        session(['admin_store_id' => (int) $this->selectedStoreId]);
        StoreContext::set((int) $this->selectedStoreId);

        $this->store = Store::findOrFail($this->selectedStoreId);
        $this->hydrateFromStore();

        session()->flash('status', 'Magasin courant mis à jour.');
    }

    public function hydrateFromStore(): void
    {
        $this->name = $this->store->name;
        $this->address = $this->store->address ?? '';
        $this->wilaya = $this->store->wilaya ?? '';
        $this->commune = $this->store->commune ?? '';
        $this->phone = $this->store->phone ?? '';
        $this->phone_secondary = $this->store->phone_secondary ?? '';
        $this->email = $this->store->email ?? '';
        $this->color = $this->store->color ?? '#1e3a5f';
        $this->currency = $this->store->currency ?? 'DA';
        $this->tax_enabled = (bool) $this->store->tax_enabled;
        $this->tax_rate = (string) ($this->store->tax_rate ?? 0);
        $this->late_fee_per_day = (string) ($this->store->late_fee_per_day ?? 0);
        $this->contract_prefix = $this->store->contract_prefix ?? 'CTR';
        $this->rental_conditions = implode("\n", $this->store->rental_conditions ?? []);
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $old = $this->store->getAttributes();

        $this->store->update([
            'name' => $this->name,
            'address' => $this->address,
            'wilaya' => $this->wilaya,
            'commune' => $this->commune,
            'phone' => $this->phone,
            'phone_secondary' => $this->phone_secondary,
            'email' => $this->email,
            'color' => $this->color ?: null,
        ]);

        AuditLogger::updated($this->store, $old, 'settings.store_updated');

        session()->flash('status', 'Informations du magasin enregistrées.');
    }

    public function saveFinancial(): void
    {
        $this->validate([
            'currency' => ['required', 'string', 'in:DA,EUR,USD,GBP,TND,MAD,CAD,AED,XOF'],
            'tax_rate' => ['numeric', 'min:0', 'max:100'],
        ]);

        $old = $this->store->getAttributes();

        $this->store->update([
            'currency' => $this->currency,
            'tax_enabled' => $this->tax_enabled,
            'tax_rate' => (float) $this->tax_rate,
            'late_fee_per_day' => (int) $this->late_fee_per_day,
            'contract_prefix' => strtoupper($this->contract_prefix),
            'rental_conditions' => array_values(array_filter(array_map('trim', explode("\n", $this->rental_conditions)))),
        ]);

        AuditLogger::updated($this->store, $old, 'settings.financial_updated');

        session()->flash('status', 'Paramètres financiers enregistrés.');
    }

    public function saveLogo(): void
    {
        $this->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $path = $this->logo->store('logos/'.$this->store->id, 'public');

        $this->store->update(['logo_path' => $path]);

        AuditLogger::log('settings.logo_updated', $this->store);

        session()->flash('status', 'Logo mis à jour.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.settings.store-settings');
    }
}