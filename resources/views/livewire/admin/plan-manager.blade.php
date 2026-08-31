<div class="space-y-6">
    <div>
        <h1 class="page-title">Plans d'abonnement</h1>
        <p class="page-subtitle">Prix, limites (articles, clients, utilisateurs, locations) et promotions affichées sur la page d'accueil.</p>
    </div>

    @if (session('status'))
        <x-flash :status="session('status')" />
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Formulaire --}}
        <form wire:submit="save" class="card card-pad space-y-5 lg:col-span-1">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $editingId ? 'Modifier le plan' : 'Nouveau plan' }}</h2>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="text-xs font-medium text-zinc-500 hover:text-zinc-700">Annuler</button>
                @endif
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-700">Nom</label>
                <input type="text" wire:model="name" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-700">Description</label>
                <textarea wire:model="description" rows="2" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium text-zinc-700">Prix (DA)</label>
                    <input type="number" min="0" wire:model="price" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-700">Période</label>
                    <select wire:model="billing_period" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="monthly">Mensuel</option>
                        <option value="yearly">Annuel</option>
                    </select>
                </div>
            </div>

            <fieldset class="space-y-3 rounded-xl border border-zinc-200 p-3">
                <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">Limites (vide = illimité)</legend>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-zinc-600">Articles</label>
                        <input type="number" min="0" wire:model="max_products" placeholder="Illimité" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-600">Clients</label>
                        <input type="number" min="0" wire:model="max_customers" placeholder="Illimité" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-600">Utilisateurs</label>
                        <input type="number" min="0" wire:model="max_users" placeholder="Illimité" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-600">Locations / mois</label>
                        <input type="number" min="0" wire:model="max_rentals_per_month" placeholder="Illimité" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-zinc-600">Stockage (Mo)</label>
                    <input type="number" min="0" wire:model="max_storage_mb" placeholder="Illimité" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                </div>
            </fieldset>

            <div>
                <label class="text-sm font-medium text-zinc-700">Fonctionnalités incluses</label>
                <div class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1.5">
                    @foreach ($featureLabels as $key => $label)
                        <label class="inline-flex items-center gap-1.5 text-xs text-zinc-700">
                            <input type="checkbox" wire:model="selected_features" value="{{ $key }}" class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <fieldset class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/50 p-3">
                <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-amber-700">Promotion (page d'accueil)</legend>
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model.live="promo_enabled" class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                    Activer une promotion sur ce plan
                </label>

                @if ($promo_enabled)
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-zinc-600">Prix promo (DA)</label>
                            <input type="number" min="0" wire:model="promo_price" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            @error('promo_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium text-zinc-600">Étiquette</label>
                            <input type="text" wire:model="promo_label" maxlength="80" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-600">Fin de la promotion (facultatif)</label>
                        <input type="datetime-local" wire:model="promo_ends_at" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        <p class="mt-1 text-xs text-zinc-500">Laissez vide pour une promotion sans date de fin.</p>
                    </div>
                @endif
            </fieldset>

            <div class="grid grid-cols-2 gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                    Actif (visible à l'inscription)
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                    <input type="checkbox" wire:model="is_popular" class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                    Mis en avant
                </label>
            </div>

            <div>
                <label class="text-xs font-medium text-zinc-600">Ordre d'affichage</label>
                <input type="number" min="0" wire:model="sort_order" class="mt-1 w-24 rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
            </div>

            <button type="submit" class="w-full rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                {{ $editingId ? 'Enregistrer' : 'Créer le plan' }}
            </button>
        </form>

        {{-- Liste --}}
        <div class="space-y-3 lg:col-span-2">
            @foreach ($plans as $plan)
                <div class="card card-pad">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-zinc-900">{{ $plan->name }}</h3>
                                @if (! $plan->is_active)
                                    <span class="badge-zinc">Inactif</span>
                                @endif
                                @if ($plan->is_popular)
                                    <span class="badge-blue">Mis en avant</span>
                                @endif
                                @if ($plan->hasActivePromo())
                                    <span class="badge-red">{{ $plan->promo_label ?: 'Promo' }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">{{ $plan->description }}</p>
                        </div>

                        <div class="text-right">
                            @if ($plan->hasActivePromo())
                                <p class="text-xs text-zinc-400 line-through">{{ money($plan->price) }}</p>
                                <p class="text-base font-semibold text-rose-600">{{ money($plan->promo_price) }}</p>
                            @else
                                <p class="text-base font-semibold text-zinc-900">{{ money($plan->price) }}</p>
                            @endif
                            <p class="text-xs text-zinc-500">/ {{ $plan->billing_period === 'yearly' ? 'an' : 'mois' }}</p>
                        </div>
                    </div>

                    @if ($plan->hasActivePromo() && $plan->promo_ends_at)
                        <p class="mt-2 text-xs text-amber-700">Promo jusqu'au {{ $plan->promo_ends_at->format('d/m/Y H:i') }}</p>
                    @endif

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-600 sm:grid-cols-4">
                        <div><dt class="text-zinc-400">Articles</dt><dd>{{ $plan->limitLabel($plan->max_products) }}</dd></div>
                        <div><dt class="text-zinc-400">Clients</dt><dd>{{ $plan->limitLabel($plan->max_customers) }}</dd></div>
                        <div><dt class="text-zinc-400">Utilisateurs</dt><dd>{{ $plan->limitLabel($plan->max_users) }}</dd></div>
                        <div><dt class="text-zinc-400">Locations/mois</dt><dd>{{ $plan->limitLabel($plan->max_rentals_per_month) }}</dd></div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="openEdit({{ $plan->id }})" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Modifier</button>
                        <button type="button" wire:click="toggleActive({{ $plan->id }})" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">
                            {{ $plan->is_active ? 'Désactiver' : 'Activer' }}
                        </button>
                        @if ($plan->promo_price !== null)
                            <button type="button" wire:click="clearPromo({{ $plan->id }})" wire:confirm="Retirer la promotion de ce plan ?" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Retirer la promo</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
