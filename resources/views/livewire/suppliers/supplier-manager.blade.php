<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Fournisseurs</h1>
                <p class="page-subtitle">{{ $suppliers->total() }} fournisseur(s) enregistré(s).</p>
            </div>
            @can('create', \App\Models\Supplier::class)
                <button type="button" wire:click="create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                    <flux:icon.plus variant="mini" /> Nouveau fournisseur
                </button>
            @endcan
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        @if ($showForm)
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $editingId ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}</h2>
                <form wire:submit="save" class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Nom</label>
                        <input wire:model="name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Téléphone</label>
                        <input wire:model="phone" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Email</label>
                        <input wire:model="email" type="email" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        @error('email') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Adresse</label>
                        <input wire:model="address" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                    </div>
                    <div class="flex gap-2 md:col-span-2">
                        <button type="submit" class="rounded-xl bg-brand-800 px-6 py-2 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                        <button type="button" wire:click="cancelForm" class="rounded-xl border border-zinc-300 px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Annuler</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card p-4">
            <div class="relative">
                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                <input wire:model.live.debounce.300ms="search" placeholder="Nom ou téléphone..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Nom</th>
                        <th class="px-4 py-3">Téléphone</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Achats</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 font-medium text-zinc-900">{{ $supplier->name }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $supplier->phone ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $supplier->email ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $supplier->purchases_count }}</td>
                            <td class="px-4 py-2.5">
                                @if ($supplier->is_active)
                                    <span class="badge-green">Actif</span>
                                @else
                                    <span class="badge-zinc">Inactif</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="inline-flex gap-1">
                                    @can('update', $supplier)
                                        <button type="button" wire:click="edit({{ $supplier->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100"><flux:icon.pencil variant="mini" /></button>
                                        <button type="button" wire:click="toggleActive({{ $supplier->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100"><flux:icon.power variant="mini" /></button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-400">Aucun fournisseur enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $suppliers->links() }}
    </div>
</div>
