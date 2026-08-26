<x-layouts.admin title="Magasin — {{ $store->name }}">
    <div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">{{ $store->name }}</h1>
            <p class="page-subtitle">Détails du magasin et administrateurs.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.stores.export', $store) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <flux:icon.arrow-down-tray variant="mini" /> Export données
            </a>
            <a href="{{ route('admin.stores.edit', $store) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>
                <flux:icon.pencil-square variant="mini" /> Modifier
            </a>
            <form method="POST" action="{{ route('admin.stores.toggle-status', $store) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-white {{ $store->status === 'active' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500' }}">
                    <flux:icon.power variant="mini" /> {{ $store->status === 'active' ? 'Suspendre' : 'Activer' }}
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <x-flash :status="session('status')" />
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-pad lg:col-span-1">
            <h2 class="text-sm font-semibold text-zinc-900">Informations</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">Statut</dt>
                    <dd>@if ($store->status === 'active') <span class="badge-green">Actif</span> @else <span class="badge-red">Suspendu</span> @endif</dd>
                </div>
                <div class="flex justify-between"><dt class="text-zinc-500">Slug</dt><dd class="font-medium">{{ $store->slug }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Token</dt><dd class="font-mono text-xs">{{ $store->token }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Téléphone</dt><dd>{{ $store->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Email</dt><dd>{{ $store->email ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Adresse</dt><dd>{{ $store->wilaya ?? '—' }} {{ $store->commune ?? '' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Responsable</dt><dd>{{ $store->manager_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Créé le</dt><dd>{{ $store->created_at?->format('d/m/Y') }}</dd></div>
            </dl>
        </div>

        <div class="card card-pad lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">Administrateurs du magasin ({{ $admins->count() }})</h2>
            </div>

            <form method="POST" action="{{ route('admin.stores.admins.store', $store) }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <input name="name" placeholder="Nom" required class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                <input name="email" type="email" placeholder="Email" required class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                <input name="password" type="password" placeholder="Mot de passe" required class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                <input name="password_confirmation" type="password" placeholder="Confirmer le mot de passe" required class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                <button type="submit" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 sm:col-span-2 lg:col-span-5">Ajouter</button>
            </form>
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

            <div class="mt-5 divide-y divide-zinc-100">
                @forelse ($admins as $admin)
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-700">{{ $admin->initials() }}</span>
                            <div>
                                <p class="text-sm font-medium text-zinc-900">{{ $admin->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $admin->email }}</p>
                            </div>
                        </div>
                        <span class="badge-blue">{{ $admin->roles->pluck('name')->join(', ') }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-zinc-500">Aucun administrateur.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Abonnement & Token --}}
    @php
        $subscription = \App\Models\Subscription::with('plan')->where('store_id', $store->id)->orderByDesc('ends_at')->first();
        $activeToken = \App\Models\StoreToken::where('store_id', $store->id)->where('status', 'active')->first();
        $allPlans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
    @endphp
    <div class="card card-pad">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-zinc-900">Abonnement & Token</h2>
                @if ($subscription)
                    <p class="mt-2 text-sm text-zinc-600">
                        Plan <strong>{{ $subscription->plan?->name }}</strong>
                        <span class="{{ \App\Models\Subscription::statusBadge($subscription->status) }}">{{ \App\Models\Subscription::statusLabels()[$subscription->status] ?? $subscription->status }}</span>
                        — expire le <strong>{{ $subscription->ends_at?->format('d/m/Y') }}</strong>
                    </p>
                @else
                    <p class="mt-2 text-sm text-rose-600">Aucun abonnement pour ce magasin.</p>
                @endif
                <p class="mt-2 text-xs text-zinc-500">Token actif : <span class="font-mono">{{ $activeToken?->token ?? 'AUCUN' }}</span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.stores.tokens.regenerate', $store) }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-zinc-300 px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Régénérer token</button>
                </form>
                @if ($activeToken)
                    <form method="POST" action="{{ route('admin.stores.tokens.revoke', $store) }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50">Désactiver token</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.stores.renew', $store) }}" class="flex flex-wrap items-end gap-2 rounded-xl bg-zinc-50 p-4">
                @csrf
                <div class="flex-1 space-y-1">
                    <label class="text-xs font-medium text-zinc-700">Renouveler avec le plan</label>
                    <select name="plan_id" required class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                        @foreach ($allPlans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ money($plan->price) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-24 space-y-1">
                    <label class="text-xs font-medium text-zinc-700">Mois</label>
                    <input name="months" type="number" min="1" max="36" value="1" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm" />
                </div>
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Renouveler</button>
            </form>

            <form method="POST" action="{{ route('admin.stores.change-plan', $store) }}" class="flex flex-wrap items-end gap-2 rounded-xl bg-zinc-50 p-4">
                @csrf
                <div class="flex-1 space-y-1">
                    <label class="text-xs font-medium text-zinc-700">Changer de plan</label>
                    <select name="plan_id" required class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                        @foreach ($allPlans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Changer</button>
            </form>
        </div>
    </div>
</div>
</x-layouts.admin>
