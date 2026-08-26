<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Clients</h1>
                <p class="page-subtitle">{{ $customers->total() }} client(s) au total.</p>
            </div>
            <button wire:click="openCreate" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                <flux:icon.plus variant="mini" /> Nouveau client
            </button>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <div class="card p-4">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="relative md:col-span-2">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Nom, téléphone, email, CIN..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <div class="flex items-center gap-1 rounded-xl bg-zinc-100 p-1">
                    <button wire:click="$set('filter', 'all')" class="flex-1 rounded-lg px-3 py-1.5 text-sm font-medium {{ $filter === 'all' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500' }}">Tous</button>
                    <button wire:click="$set('filter', 'favorite')" class="flex-1 rounded-lg px-3 py-1.5 text-sm font-medium {{ $filter === 'favorite' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500' }}">Favoris</button>
                </div>
            </div>
        </div>

        @if ($showForm)
            <div class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">{{ $editingId ? 'Modifier le client' : 'Nouveau client' }}</h2>
                    <button wire:click="$set('showForm', false)" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100"><flux:icon.x-mark variant="mini" /></button>
                </div>
                <form wire:submit="save" class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Prénom *</label>
                        <input wire:model="first_name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('first_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Nom *</label>
                        <input wire:model="last_name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('last_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Téléphone *</label>
                        <input wire:model="phone" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('phone') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                        <div>
                            <label class="text-sm font-medium text-zinc-700">Adresse</label>
                            <input wire:model="address" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-zinc-700">Wilaya</label>
                                <input wire:model="wilaya" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-zinc-700">Commune</label>
                                <input wire:model="commune" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Email</label>
                        <input wire:model="email" type="email" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('email') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">CIN</label>
                        <input wire:model="cin" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Carte d'identité" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Date de naissance</label>
                        <input wire:model="birth_date" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                        @error('birth_date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700">Adresse</label>
                        <input wire:model="address" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700">Notes</label>
                        <textarea wire:model="notes" rows="3" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-zinc-700">
                        <input wire:model="favorite" type="checkbox" class="rounded border-zinc-300 text-brand-800 focus:ring-brand-600" />
                        Client favori
                    </label>
                    <div class="flex justify-end gap-2 md:col-span-2">
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Annuler</button>
                        <button type="submit" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Téléphone</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">CIN</th>
                        <th class="px-4 py-3 text-center">Favori</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-800">{{ strtoupper(substr($customer->first_name, 0, 1)) }}{{ strtoupper(substr($customer->last_name, 0, 1)) }}</span>
                                    <div>
                                        <a href="{{ route('customers.show', $customer) }}" wire:navigate class="font-medium text-zinc-900 hover:text-brand-700">{{ $customer->full_name }}</a>
                                        @if ($customer->birth_date)
                                            <span class="block text-xs text-zinc-400">{{ $customer->birth_date?->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-zinc-600">{{ $customer->phone }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $customer->email ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-500">{{ $customer->cin ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <button wire:click="toggleFavorite({{ $customer->id }})" class="{{ $customer->favorite ? 'text-amber-400' : 'text-zinc-300 hover:text-zinc-400' }}" title="Favori">
                                    <flux:icon.star variant="mini" />
                                </button>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('customers.show', $customer) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Voir" wire:navigate><flux:icon.eye variant="mini" /></a>
                                    <button wire:click="openEdit({{ $customer->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Modifier"><flux:icon.pencil-square variant="mini" /></button>
                                    <button wire:click="deleteCustomer({{ $customer->id }})" wire:confirm="Supprimer ce client ?" class="rounded-lg p-1.5 text-zinc-500 hover:bg-rose-50 hover:text-rose-600" title="Supprimer"><flux:icon.trash variant="mini" /></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Aucun client trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-100 p-4">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</div>