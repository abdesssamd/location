<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Paramètres du magasin</h1>
            <p class="page-subtitle">Personnalisez votre magasin : informations, logo, devise, TVA, conditions de location.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        @if (Auth::user()->is_super_admin)
            <div class="card card-pad">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-medium text-zinc-700">Magasin concerné</label>
                    <select wire:model="selectedStoreId" wire:change="selectStore" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        @foreach (\App\Models\Store::orderBy('name')->get() as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        @if ($store)
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Logo du magasin</h2>

                <div class="mt-4 flex items-center gap-4">
                    @if ($store->logo_path)
                        <img src="{{ Storage::disk('public')->url($store->logo_path) }}" alt="Logo" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-zinc-200" />
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-800 text-white">
                            <x-app-logo-icon class="size-8" />
                        </span>
                    @endif
                    <div class="flex-1">
                        <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-800 hover:file:bg-brand-100" />
                        <button wire:click="saveLogo" wire:loading.attr="disabled" class="mt-3 rounded-lg bg-brand-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700">
                            Enregistrer le logo
                        </button>
                    </div>
                </div>
                @error('logo') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="card card-pad lg:col-span-2">
                <h2 class="text-sm font-semibold text-zinc-900">Informations générales</h2>
                <form wire:submit="saveGeneral" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <label class="text-sm font-medium text-zinc-700">Nom du magasin</label>
                        <input wire:model="name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Téléphone</label>
                        <input wire:model="phone" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Téléphone secondaire</label>
                        <input wire:model="phone_secondary" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Email</label>
                        <input wire:model="email" type="email" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Adresse</label>
                        <input wire:model="address" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Wilaya</label>
                        <input wire:model="wilaya" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Commune</label>
                        <input wire:model="commune" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Couleur de la marque</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="color" class="h-10 w-12 cursor-pointer rounded-xl border border-zinc-300 bg-white p-1" />
                            <input wire:model="color" maxlength="7" placeholder="#1e3a5f" class="w-full rounded-xl border border-zinc-300 px-3 py-2 font-mono text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        </div>
                        @error('color') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Paramètres financiers & contrats</h2>
            <form wire:submit="saveFinancial" class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Devise</label>
                    <select wire:model="currency" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                        @foreach (['DA' => 'Dinar algérien (DA)', 'EUR' => 'Euro (€)', 'USD' => 'Dollar US ($)', 'GBP' => 'Livre (£)', 'TND' => 'Dinar tunisien (DT)', 'MAD' => 'Dirham (DH)', 'CAD' => 'Dollar canadien (CA$)', 'AED' => 'Dirham UAE (AED)', 'XOF' => 'Franc CFA (FCFA)'] as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Préfixe des contrats</label>
                    <input wire:model="contract_prefix" maxlength="10" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="tax_enabled" id="tax_enabled" class="h-4 w-4 rounded border-zinc-300" />
                    <label for="tax_enabled" class="text-sm text-zinc-700">Appliquer la TVA</label>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Taux TVA (%)</label>
                    <input wire:model="tax_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Pénalité retard (DA / jour)</label>
                    <input wire:model="late_fee_per_day" type="number" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <label class="text-sm font-medium text-zinc-700">Conditions de location (une par ligne)</label>
                    <textarea wire:model="rental_conditions" rows="5" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                </div>
            </form>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Configuration du catalogue</h2>
            <p class="mt-1 text-sm text-zinc-500">Gérez les catégories d'articles utilisées pour composer vos packs et organiser votre stock.</p>
            <a href="{{ route('categories.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-2 text-sm font-medium text-brand-800 hover:bg-brand-100">
                <flux:icon.tag class="size-4" />
                Gérer les catégories
            </a>
        </div>
        @else
            <div class="card card-pad">
                <p class="text-sm text-zinc-500">Aucun magasin disponible. Créez un magasin pour configurer ses paramètres.</p>
            </div>
        @endif
    </div>
</div>