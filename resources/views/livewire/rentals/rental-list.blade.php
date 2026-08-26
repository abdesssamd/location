<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Locations</h1>
                <p class="page-subtitle">{{ $rentals->total() }} location(s) au total.</p>
            </div>
            @can('rentals.create')
                <a href="{{ route('rentals.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700" wire:navigate>
                    <flux:icon.plus variant="mini" /> Nouvelle réservation
                </a>
            @endcan
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <div class="card p-4">
            <div class="grid gap-3 md:grid-cols-6">
                <div class="relative md:col-span-2">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Référence, client, téléphone..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="status" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="packFilter" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les types</option>
                    <option value="with">Avec pack</option>
                    <option value="without">Sans pack</option>
                </select>
                <input wire:model.live="from" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" title="Du" />
                <input wire:model.live="to" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" title="Au" />
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Période</th>
                        <th class="px-4 py-3">Articles</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($rentals as $rental)
                        @php $hasPack = $rental->items->contains(fn($it) => (bool) $it->is_pack_component); @endphp
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 font-mono text-xs font-medium text-zinc-700">{{ $rental->reference }}</td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900">{{ $rental->customer?->full_name }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $rental->start_date?->format('d/m') }} → {{ $rental->end_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">
                                {{ $rental->items->sum('quantity') }}
                                @if ($hasPack)
                                    <span class="ml-1 badge-blue">Pack</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 font-semibold text-zinc-900">{{ money($rental->total) }}</td>
                            <td class="px-4 py-2.5"><span class="{{ \App\Models\Rental::statusBadge($rental->status) }}">{{ $statusLabels[$rental->status] ?? $rental->status }}</span></td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-1">
                                    @if ($hasPack && auth()->user()->can('contracts.view'))
                                        <a href="{{ route('contracts.pack-return.show', $rental) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Fiche retour pack"><flux:icon.document-text variant="mini" /></a>
                                    @endif
                                    @if ($hasPack && auth()->user()->can('contracts.pdf'))
                                        <a href="{{ route('contracts.pack-return.pdf', $rental) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="PDF retour pack"><flux:icon.arrow-down-tray variant="mini" /></a>
                                    @endif
                                    <a href="{{ route('rentals.show', $rental) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Voir" wire:navigate><flux:icon.eye variant="mini" /></a>
                                    @if (in_array($rental->status, ['reserved', 'active']) && auth()->user()->can('rentals.create'))
                                        <a href="{{ route('rentals.edit', $rental) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Modifier" wire:navigate><flux:icon.pencil-square variant="mini" /></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-500">Aucune location trouvée.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-100 p-4">
                {{ $rentals->links() }}
            </div>
        </div>
    </div>
</div>
