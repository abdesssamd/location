<div class="space-y-6">
    <div>
        <h1 class="page-title">Paramètres généraux</h1>
        <p class="page-subtitle">Inscription publique des magasins et période de démonstration offerte.</p>
    </div>

    @if (session('status'))
        <x-flash :status="session('status')" />
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <form wire:submit="save" class="card card-pad space-y-6 lg:col-span-2">
            <div>
                <h2 class="text-sm font-semibold text-zinc-900">Inscription des magasins</h2>
                <p class="mt-1 text-xs text-zinc-500">Contrôle le formulaire « Créer mon magasin » de la page d'accueil.</p>
            </div>

            <label class="flex items-start gap-3 rounded-xl border border-zinc-200 p-4">
                <input type="checkbox" wire:model="signupEnabled" class="mt-0.5 size-4 rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                <span>
                    <span class="block text-sm font-medium text-zinc-900">Autoriser l'inscription en ligne</span>
                    <span class="block text-xs text-zinc-500">Si désactivé, seuls les magasins créés depuis l'espace admin peuvent accéder à l'application.</span>
                </span>
            </label>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-zinc-900">Traitement des demandes</legend>

                <label class="flex items-start gap-3 rounded-xl border border-zinc-200 p-4 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60">
                    <input type="radio" value="auto" wire:model="signupMode" class="mt-0.5 size-4 border-zinc-300 text-brand-800 focus:ring-brand-600" />
                    <span>
                        <span class="block text-sm font-medium text-zinc-900">Acceptation automatique</span>
                        <span class="block text-xs text-zinc-500">Le magasin est actif immédiatement et sa démonstration démarre à l'inscription.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-xl border border-zinc-200 p-4 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60">
                    <input type="radio" value="manual" wire:model="signupMode" class="mt-0.5 size-4 border-zinc-300 text-brand-800 focus:ring-brand-600" />
                    <span>
                        <span class="block text-sm font-medium text-zinc-900">Validation manuelle</span>
                        <span class="block text-xs text-zinc-500">La demande reste en attente ; la démonstration démarre le jour où vous l'acceptez.</span>
                    </span>
                </label>
                @error('signupMode') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-900" for="trialDays">Jours de démonstration offerts</label>
                    <input id="trialDays" type="number" min="0" max="365" wire:model="trialDays" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    <p class="mt-1 text-xs text-zinc-500">0 = aucune démonstration, l'abonnement doit être payé dès l'activation.</p>
                    @error('trialDays') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900" for="trialPlanId">Plan utilisé pendant la démonstration</label>
                    <select id="trialPlanId" wire:model="trialPlanId" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Premier plan actif</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ money($plan->price) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-zinc-500">Détermine les limites (articles, clients, utilisateurs) pendant l'essai.</p>
                    @error('trialPlanId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    <span wire:loading.remove wire:target="save">Enregistrer</span>
                    <span wire:loading wire:target="save">Enregistrement…</span>
                </button>
            </div>
        </form>

        <div class="card card-pad space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-zinc-900">Demandes en attente</h2>
                <p class="mt-1 text-xs text-zinc-500">Magasins inscrits depuis la page d'accueil, non encore activés.</p>
            </div>

            @forelse ($pendingStores as $store)
                <div class="rounded-xl border border-zinc-200 p-3">
                    <p class="text-sm font-medium text-zinc-900">{{ $store->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $store->email }}{{ $store->wilaya ? ' — '.$store->wilaya : '' }}</p>
                    <p class="mt-1 text-xs text-zinc-400">Demandé le {{ $store->created_at->format('d/m/Y') }}</p>
                    <div class="mt-3 flex gap-2">
                        <form method="POST" action="{{ route('admin.stores.approve', $store) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500">Accepter</button>
                        </form>
                        <a href="{{ route('admin.stores.show', $store) }}" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Détail</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500">Aucune demande en attente.</p>
            @endforelse
        </div>
    </div>
</div>
