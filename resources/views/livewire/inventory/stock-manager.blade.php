<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Stock</h1>
            <p class="page-subtitle">Suivi des entrées et sorties de stock.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card card-pad flex items-center gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-800"><flux:icon.cube variant="mini" /></span>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900">{{ number_format($totalStock, 0, ',', ' ') }}</p>
                    <p class="text-xs text-zinc-500">Articles en stock</p>
                </div>
            </div>
            <div class="card card-pad flex items-center gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><flux:icon.exclamation-triangle variant="mini" /></span>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900">{{ $lowStockCount }}</p>
                    <p class="text-xs text-zinc-500">Stock bas (≤ 2)</p>
                </div>
            </div>
            <div class="card card-pad flex items-center gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><flux:icon.arrow-path variant="mini" /></span>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900">{{ $todayCount }}</p>
                    <p class="text-xs text-zinc-500">Mouvements aujourd'hui</p>
                </div>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Nouveau mouvement</h2>
            <form wire:submit="addMovement" class="mt-4 grid gap-4 md:grid-cols-5">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Article</label>
                    <select wire:model="product_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                        <option value="">— Choisir —</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->reference }}) — {{ $p->quantity }} en stock</option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Type</label>
                    <select wire:model="type" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Quantité</label>
                    <input wire:model="quantity" type="number" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    @error('quantity') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700">Motif</label>
                    <input wire:model="reason" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Optionnel" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Enregistrer</button>
                </div>
            </form>
            <p class="mt-3 text-xs text-zinc-500">Astuce : pour un ajustement, saisissez une quantité négative pour une sortie, positive pour une entrée.</p>
        </div>

        <div class="card overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-zinc-100 p-4 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Rechercher un article..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="filterType" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les types</option>
                    @foreach ($typeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input wire:model.live="from" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                <input wire:model.live="to" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
            </div>

            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Article</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Quantité</th>
                        <th class="px-4 py-3">Motif</th>
                        <th class="px-4 py-3">Par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($movements as $m)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-4 py-2.5 text-zinc-500">{{ $m->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5">
                                <a href="{{ route('products.show', $m->product) }}" wire:navigate class="font-medium text-zinc-900 hover:text-brand-700">{{ $m->product?->name }}</a>
                                <span class="block font-mono text-xs text-zinc-400">{{ $m->product?->reference }}</span>
                            </td>
                            <td class="px-4 py-2.5"><span class="{{ \App\Models\StockMovement::typeBadge($m->type) }}">{{ $typeLabels[$m->type] ?? $m->type }}</span></td>
                            <td class="px-4 py-2.5 font-semibold {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $m->reason ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-zinc-500">{{ $m->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Aucun mouvement enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-100 p-4">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>