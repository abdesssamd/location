<?php

namespace App\Http\Controllers\Admin;

use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(): View
    {
        $stores = Store::withCount('users')->latest()->paginate(12);

        return view('admin.stores.index', compact('stores'));
    }

    public function create(): View
    {
        return view('admin.stores.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:stores,slug'],
            'address' => ['nullable', 'string', 'max:255'],
            'wilaya' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'admin_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data['token'] = ReferenceGenerator::storeToken();
        $data['status'] = 'active';

        $store = Store::create($data);

        // Token initial + abonnement d'essai 14 jours
        \App\Services\SubscriptionService::generateToken($store, auth()->id());
        $trialPlan = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->first();
        if ($trialPlan) {
            \App\Services\SubscriptionService::createSubscription($store, $trialPlan, \App\Models\Subscription::STATUS_TRIAL, 14, auth()->id());
        }

        $adminPassword = $data['admin_password'] ?? Str::password(12);

        $admin = User::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $adminPassword,
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $admin->assignRole('admin');

        try {
            $admin->notify(new \App\Notifications\StoreAdminWelcomeNotification($store, $adminPassword));
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Magasin créé, mais l\'email de bienvenue n\'a pas pu être envoyé : '.$e->getMessage());
        }

        AuditLogger::log('store.created', $store, null, $store->getAttributes(), null);

        return redirect()
            ->route('admin.stores.show', $store)
            ->with('status', 'Magasin créé avec succès. Un email a été envoyé à l\'administrateur.');
    }

    public function show(Store $store): View
    {
        $store->loadCount('users');
        $admins = $store->users()->with('roles')->get();

        return view('admin.stores.show', compact('store', 'admins'));
    }

    public function edit(Store $store): View
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:stores,slug,'.$store->id],
            'address' => ['nullable', 'string', 'max:255'],
            'wilaya' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $old = $store->getAttributes();
        $store->update($data);

        AuditLogger::log('store.updated', $store, $old, $store->getChanges(), null);

        return redirect()
            ->route('admin.stores.show', $store)
            ->with('status', 'Magasin mis à jour.');
    }

    public function toggleStatus(Store $store): RedirectResponse
    {
        $store->status = $store->status === 'active' ? 'suspended' : 'active';
        $store->suspended_at = $store->status === 'suspended' ? now() : null;
        $store->save();

        AuditLogger::log('store.status_changed', $store, null, ['status' => $store->status], null);

        return back()->with('status', 'Statut du magasin mis à jour.');
    }

    public function createAdmin(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = User::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
            'locale' => 'fr',
        ]);

        $admin->assignRole('admin');

        try {
            $admin->notify(new \App\Notifications\StoreAdminWelcomeNotification($store, $data['password']));
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Administrateur créé, mais l\'email de bienvenue n\'a pas pu être envoyé : '.$e->getMessage());
        }

        AuditLogger::log('store.admin_created', $admin, null, ['email' => $admin->email], null);

        return redirect()
            ->route('admin.stores.show', $store)
            ->with('status', 'Administrateur magasin créé.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        AuditLogger::log('store.deleted', $store, $store->getAttributes(), null, null);
        $store->delete();

        return redirect()->route('admin.stores.index')->with('status', 'Magasin supprimé.');
    }

    public function resetAdminPassword(Store $store, User $admin): RedirectResponse
    {
        if ($admin->store_id !== $store->id) {
            abort(404);
        }

        $password = Str::password(12);
        $admin->update(['password' => $password]);

        try {
            $admin->notify(new \App\Notifications\StoreAdminPasswordResetNotification($store, $password));
            $message = 'Un nouveau mot de passe a été généré et envoyé à '.$admin->email.'.';
        } catch (\Throwable $e) {
            report($e);
            $message = 'Nouveau mot de passe généré pour '.$admin->email.', mais l\'email n\'a pas pu être envoyé : '.$e->getMessage().' — vérifiez la configuration MAIL_* (.env) et videz le cache de config (php artisan config:clear).';
        }

        AuditLogger::log('store.admin_password_reset', $admin, null, ['email' => $admin->email], null);

        return back()->with('status', $message);
    }
}