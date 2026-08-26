<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Contrats</h1>
            <p class="page-subtitle">Génération des contrats de location ({{ $rentals->total() }} au total).</p>
        </div>

        <div class="card p-4">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="relative md:col-span-2">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Référence ou client..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="status" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    <option value="active">Active</option>
                    <option value="completed">Terminée</option>
                </select>
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Période</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Contrat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($rentals as $rental)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 font-mono text-xs font-medium text-zinc-700">{{ $rental->reference }}</td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900">{{ $rental->customer?->full_name }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $rental->start_date?->format('d/m/Y') }} → {{ $rental->end_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 font-semibold text-zinc-900">{{ number_format($rental->total, 0, ',', ' ') }} {{ $store->currency }}</td>
                            <td class="px-4 py-2.5"><span class="{{ \App\Models\Rental::statusBadge($rental->status) }}">{{ $rental->status === 'active' ? 'Active' : 'Terminée' }}</span></td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('contracts.show', $rental) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">
                                        <flux:icon.eye variant="mini" /> Voir
                                    </a>
                                    <a href="{{ route('contracts.pdf', $rental) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700">
                                        <flux:icon.arrow-down-tray variant="mini" /> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Aucune location éligible à un contrat.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-100 p-4">
                {{ $rentals->links() }}
            </div>
        </div>
    </div>
</div>