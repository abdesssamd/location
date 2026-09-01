<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Rapports</h1>
            <p class="page-subtitle">Analyse de l'activité du magasin.</p>
        </div>

        <div class="card p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="space-y-1">
                    <label class="text-sm text-zinc-600">Du</label>
                    <input wire:model.live="from" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm text-zinc-600">Au</label>
                    <input wire:model.live="to" type="date" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                </div>
                <button wire:click="exportPaymentsCsv" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                    <flux:icon.arrow-down-tray variant="mini" /> Paiements (CSV)
                </button>
                <button wire:click="exportExcel" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                    <flux:icon.arrow-down-tray variant="mini" /> Rapport (Excel)
                </button>
                <button wire:click="exportPdf" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                    <flux:icon.arrow-down-tray variant="mini" /> Rapport (PDF)
                </button>
            </div>
        </div>

        <div class="card card-pad flex flex-wrap items-center justify-between gap-4 border-l-4 {{ $netProfit >= 0 ? 'border-l-emerald-500' : 'border-l-rose-500' }}">
            <div>
                <p class="text-xs text-zinc-500">Bénéfice net sur la période (location + vente − dépenses)</p>
                <p class="mt-1 text-3xl font-bold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ money($netProfit) }}</p>
            </div>
            <div class="flex gap-6 text-sm">
                <div>
                    <p class="text-xs text-zinc-500">Revenus</p>
                    <p class="font-semibold text-zinc-900">{{ money($revenue + $saleRevenue) }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500">Dépenses</p>
                    <p class="font-semibold text-rose-600">− {{ money($expenseTotal) }}</p>
                </div>
            </div>
        </div>

        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Location</p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Chiffre d'affaires location</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($revenue) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Locations</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $rentalCount }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Panier moyen</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($average) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Remboursé</p>
                <p class="mt-1 text-2xl font-semibold text-rose-600">{{ money($refunds) }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Chiffre d'affaires mensuel</h2>
                <div class="mt-4 flex h-44 items-end gap-2">
                    @foreach ($monthly as $month)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <span class="text-[10px] text-zinc-400">{{ number_format($month['amount'] / 1000, 1, ',') }}k</span>
                            <div class="w-full rounded-t-lg bg-brand-800 transition hover:bg-brand-700" style="height: {{ max(2, round($month['amount'] / $maxMonthly * 130)) }}px" title="{{ $month['label'] }}"></div>
                            <span class="text-[10px] text-zinc-500">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Locations par statut</h2>
                <div class="mt-4 space-y-3">
                    @php
                        $statusLabels = \App\Models\Rental::statusLabels();
                        $totalStatuses = max(1, array_sum($statuses->toArray()));
                        $colors = ['reserved' => 'bg-blue-500', 'active' => 'bg-emerald-500', 'completed' => 'bg-zinc-400', 'cancelled' => 'bg-rose-500'];
                    @endphp
                    @foreach ($statuses as $status => $count)
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-zinc-600">{{ $statusLabels[$status] ?? $status }}</span>
                                <span class="font-medium text-zinc-900">{{ $count }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full {{ $colors[$status] }}" style="width: {{ round($count / $totalStatuses * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Top articles loués</h2>
            <div class="mt-4 space-y-3">
                @forelse ($topProducts as $index => $product)
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">{{ $index + 1 }}</span>
                        <div class="flex-1">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-zinc-800">{{ $product['name'] }}</span>
                                <span class="text-zinc-500">{{ $product['qty'] }} × · {{ money($product['revenue']) }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full bg-brand-800" style="width: {{ round($product['qty'] / $maxTop * 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500">Aucune location sur la période.</p>
                @endforelse
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Top packs loués</h2>
            <div class="mt-4 space-y-3">
                @forelse ($topPacks as $index => $pack)
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">{{ $index + 1 }}</span>
                        <div class="flex-1">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-zinc-800">{{ $pack['label'] }}</span>
                                <span class="text-zinc-500">{{ $pack['rentals'] }} loc. · {{ money($pack['revenue']) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500">Aucun pack loué sur la période.</p>
                @endforelse
            </div>
        </div>

        <p class="pt-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Vente</p>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Chiffre d'affaires vente</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($saleRevenue) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Ventes</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $saleCount }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Panier moyen</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ money($saleAverage) }}</p>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Chiffre d'affaires vente (mensuel)</h2>
            <div class="mt-4 flex h-44 items-end gap-2">
                @foreach ($monthlySales as $month)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-[10px] text-zinc-400">{{ number_format($month['amount'] / 1000, 1, ',') }}k</span>
                        <div class="w-full rounded-t-lg bg-violet-600 transition hover:bg-violet-500" style="height: {{ max(2, round($month['amount'] / $maxMonthlySales * 130)) }}px" title="{{ $month['label'] }}"></div>
                        <span class="text-[10px] text-zinc-500">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Top articles vendus</h2>
            <div class="mt-4 space-y-3">
                @forelse ($topSoldProducts as $index => $product)
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">{{ $index + 1 }}</span>
                        <div class="flex-1">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-zinc-800">{{ $product['name'] }}</span>
                                <span class="text-zinc-500">{{ $product['qty'] }} × · {{ money($product['revenue']) }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full bg-violet-600" style="width: {{ round($product['qty'] / $maxTopSold * 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500">Aucune vente sur la période.</p>
                @endforelse
            </div>
        </div>

        <p class="pt-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Dépenses</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Total des dépenses</p>
                <p class="mt-1 text-2xl font-semibold text-rose-600">{{ money($expenseTotal) }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Bénéfice net</p>
                <p class="mt-1 text-2xl font-semibold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ money($netProfit) }}</p>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Dépenses par catégorie</h2>
            <div class="mt-4 space-y-3">
                @forelse ($expensesByCategory as $category)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-zinc-800">{{ $category['name'] }}</span>
                            <span class="text-zinc-500">{{ money($category['amount']) }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-zinc-100">
                            <div class="h-full rounded-full bg-rose-500" style="width: {{ round($category['amount'] / $maxExpenseCategory * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500">Aucune dépense sur la période.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
