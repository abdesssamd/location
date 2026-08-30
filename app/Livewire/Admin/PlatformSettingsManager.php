<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Store;
use App\Services\AuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Paramètres généraux de la plateforme : inscription publique des magasins
 * et période de démonstration offerte à la première inscription.
 */
#[Layout('components.layouts.admin')]
class PlatformSettingsManager extends Component
{
    public bool $signupEnabled = true;
    public string $signupMode = PlatformSetting::MODE_MANUAL;
    public int|string $trialDays = 14;
    public $trialPlanId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $this->signupEnabled = PlatformSetting::signupEnabled();
        $this->signupMode = (string) PlatformSetting::get('signup_mode');
        $this->trialDays = PlatformSetting::trialDays();
        $this->trialPlanId = PlatformSetting::get('trial_plan_id');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $this->validate([
            'signupMode' => ['required', 'in:auto,manual'],
            'trialDays' => ['required', 'integer', 'min:0', 'max:365'],
            'trialPlanId' => ['nullable', 'exists:plans,id'],
        ], [], [
            'signupMode' => 'mode d\'acceptation',
            'trialDays' => 'durée de démonstration',
            'trialPlanId' => 'plan de démonstration',
        ]);

        PlatformSetting::put('signup_enabled', $this->signupEnabled);
        PlatformSetting::put('signup_mode', $this->signupMode);
        PlatformSetting::put('trial_days', (int) $this->trialDays);
        PlatformSetting::put('trial_plan_id', $this->trialPlanId ? (int) $this->trialPlanId : null);

        AuditLogger::log('platform.settings_updated', null, null, [
            'signup_enabled' => $this->signupEnabled,
            'signup_mode' => $this->signupMode,
            'trial_days' => (int) $this->trialDays,
        ]);

        session()->flash('status', 'Paramètres généraux enregistrés.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.platform-settings', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'pendingStores' => Store::where('status', 'pending')->latest()->get(),
        ]);
    }
}
