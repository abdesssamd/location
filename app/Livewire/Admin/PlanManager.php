<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use App\Services\AuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Gestion des plans d'abonnement par le super admin : prix, limites
 * (articles, clients, utilisateurs, locations/mois) et promotions
 * affichées sur la page d'accueil publique.
 */
#[Layout('components.layouts.admin')]
class PlanManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';
    public string $description = '';
    public int|string $price = 0;
    public string $billing_period = Plan::BILLING_MONTHLY;

    public string $max_users = '';
    public string $max_products = '';
    public string $max_customers = '';
    public string $max_rentals_per_month = '';
    public string $max_storage_mb = '';

    /** @var array<int, string> */
    public array $selected_features = [];

    public bool $is_active = true;
    public bool $is_popular = false;
    public int|string $sort_order = 0;

    // Promotion
    public bool $promo_enabled = false;
    public string $promo_price = '';
    public string $promo_ends_at = '';
    public string $promo_label = 'Promo';

    public function mount(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
    }

    public function openEdit(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        $this->editingId = $plan->id;
        $this->name = $plan->name;
        $this->description = (string) $plan->description;
        $this->price = $plan->price;
        $this->billing_period = $plan->billing_period;
        $this->max_users = $plan->max_users === null ? '' : (string) $plan->max_users;
        $this->max_products = $plan->max_products === null ? '' : (string) $plan->max_products;
        $this->max_customers = $plan->max_customers === null ? '' : (string) $plan->max_customers;
        $this->max_rentals_per_month = $plan->max_rentals_per_month === null ? '' : (string) $plan->max_rentals_per_month;
        $this->max_storage_mb = $plan->max_storage_mb === null ? '' : (string) $plan->max_storage_mb;
        $this->selected_features = $plan->features ?? [];
        $this->is_active = (bool) $plan->is_active;
        $this->is_popular = (bool) $plan->is_popular;
        $this->sort_order = $plan->sort_order;

        $this->promo_enabled = $plan->promo_price !== null;
        $this->promo_price = $plan->promo_price === null ? '' : (string) $plan->promo_price;
        $this->promo_ends_at = $plan->promo_ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->promo_label = $plan->promo_label ?: 'Promo';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'billing_period' => ['required', 'in:monthly,yearly'],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_customers' => ['nullable', 'integer', 'min:0'],
            'max_rentals_per_month' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'promo_price' => $this->promo_enabled ? ['required', 'integer', 'min:0', 'lt:price'] : ['nullable'],
            'promo_ends_at' => ['nullable', 'date'],
            'promo_label' => ['nullable', 'string', 'max:80'],
        ], [
            'promo_price.lt' => 'Le prix promo doit être inférieur au prix normal.',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->editingId ? Plan::findOrFail($this->editingId)->slug : \Illuminate\Support\Str::slug($this->name),
            'description' => $this->description ?: null,
            'price' => (int) $this->price,
            'billing_period' => $this->billing_period,
            'max_users' => $this->max_users === '' ? null : (int) $this->max_users,
            'max_products' => $this->max_products === '' ? null : (int) $this->max_products,
            'max_customers' => $this->max_customers === '' ? null : (int) $this->max_customers,
            'max_rentals_per_month' => $this->max_rentals_per_month === '' ? null : (int) $this->max_rentals_per_month,
            'max_storage_mb' => $this->max_storage_mb === '' ? null : (int) $this->max_storage_mb,
            'features' => array_values($this->selected_features),
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,
            'sort_order' => (int) $this->sort_order,
            'promo_price' => $this->promo_enabled && $this->promo_price !== '' ? (int) $this->promo_price : null,
            'promo_ends_at' => $this->promo_enabled && $this->promo_ends_at !== '' ? $this->promo_ends_at : null,
            'promo_label' => $this->promo_enabled ? ($this->promo_label ?: 'Promo') : null,
        ];

        if ($this->editingId) {
            $plan = Plan::findOrFail($this->editingId);
            $old = $plan->getAttributes();
            $plan->update($data);
            AuditLogger::updated($plan, $old, 'plan.updated');
            session()->flash('status', 'Plan « '.$plan->name.' » mis à jour.');
        } else {
            $plan = Plan::create($data);
            AuditLogger::created($plan, 'plan.created');
            session()->flash('status', 'Plan « '.$plan->name.' » créé.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);
        AuditLogger::log('plan.active_toggled', $plan);
    }

    public function clearPromo(int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $old = $plan->getAttributes();
        $plan->update(['promo_price' => null, 'promo_ends_at' => null, 'promo_label' => null]);
        AuditLogger::updated($plan, $old, 'plan.promo_cleared');

        if ($this->editingId === $planId) {
            $this->promo_enabled = false;
            $this->promo_price = '';
            $this->promo_ends_at = '';
        }

        session()->flash('status', 'Promotion retirée.');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->price = 0;
        $this->billing_period = Plan::BILLING_MONTHLY;
        $this->max_users = '';
        $this->max_products = '';
        $this->max_customers = '';
        $this->max_rentals_per_month = '';
        $this->max_storage_mb = '';
        $this->selected_features = [];
        $this->is_active = true;
        $this->is_popular = false;
        $this->sort_order = 0;
        $this->promo_enabled = false;
        $this->promo_price = '';
        $this->promo_ends_at = '';
        $this->promo_label = 'Promo';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.plan-manager', [
            'plans' => Plan::orderBy('sort_order')->get(),
            'featureLabels' => Plan::featureLabels(),
        ]);
    }
}
