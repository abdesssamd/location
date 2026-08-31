<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Ventes</h1>
                <p class="page-subtitle">{{ $sales->total() }} vente(s) au total · {{ money($totalRevenue) }} encaissé(s) aujourd'hui.</p>
            </div>
            @can('sales.create')
                <a href="{{ route('sales.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700" wire:navigate>
                    <flux:icon.plus variant="mini" /> Nouvelle vente
                </a>
            @endcan
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <div class="card p-4">
            <div class="grid gap-3 md:grid-cols-4">
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
                <div class="grid grid-cols-2 gap-2">
                    <input wire:model.live="from" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" title="Du" />
                    <input wire:model.live="to" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" title="Au" />
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Référence</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Articles</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($sales as $sale)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 font-mono text-xs font-medium text-zinc-700">{{ $sale->reference }}</td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900">{{ $sale->customer?->full_name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $sale->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $sale->items->sum('quantity') }} article(s)</td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900 tabular-nums">{{ money($sale->total) }}</td>
                            <td class="px-4 py-2.5"><span class="{{ \App\Models\Sale::statusBadge($sale->status) }}">{{ $statusLabels[$sale->status] ?? $sale->status }}</span></td>
                            <td class="px-4 py-2.5 text-right">
                                <a href="{{ route('sales.show', $sale) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" wire:navigate><flux:icon.eye variant="mini" /></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-400">Aucune vente pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sales->links() }}
    </div>
</div>
