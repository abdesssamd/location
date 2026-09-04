<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title">Tableau de bord</h1>
                <p class="page-subtitle">Bienvenue, {{ auth()->user()->name }}.</p>
            </div>
            @can('rentals.create')
                <a href="{{ route('rentals.create') }}" class="btn btn-primary" wire:navigate>
                    <flux:icon.plus variant="mini" /> Nouvelle réservation
                </a>
            @endcan
        </div>

        @php
            $subService = \App\Services\SubscriptionService::store();
        @endphp
        @if (auth()->user()->store_id && ! auth()->user()->is_super_admin)
            @if ($subService->inGrace())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <span>Votre abonnement a expiré. Période de grâce jusqu'au {{ $subService->graceEndsAt()?->format('d/m/Y') }}.</span>
                    <a href="{{ route('subscription.plans') }}" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500">Renouveler</a>
                </div>
            @elseif (($threshold = $subService->warningThreshold()) !== null && $subService->isActive())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <span>Votre abonnement expire dans {{ $subService->daysRemaining() }} jour(s).</span>
                    <a href="{{ route('subscription.plans') }}" class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-500">Renouveler</a>
                </div>
            @endif
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @if ($canSeeFinance)
                <div class="stat-card animate-fade-up" style="animation-delay: 0s">
                    <div class="stat-card__icon"><flux:icon.banknotes variant="mini" /></div>
                    <p class="stat-card__label mt-4">Encaissé aujourd'hui</p>
                    <p class="stat-card__value">{{ money($revenueToday) }}</p>
                </div>
            @else
                <div class="stat-card animate-fade-up" style="animation-delay: 0s">
                    <div class="stat-card__icon"><flux:icon.users variant="mini" /></div>
                    <p class="stat-card__label mt-4">Clients</p>
                    <p class="stat-card__value">{{ $customerCount }}</p>
                </div>
            @endif
            <div class="stat-card animate-fade-up" style="animation-delay: 0.06s">
                <div class="stat-card__icon"><flux:icon.archive-box variant="mini" /></div>
                <p class="stat-card__label mt-4">Locations en cours</p>
                <p class="stat-card__value text-emerald-600">{{ $activeRentals }}</p>
            </div>
            <div class="stat-card animate-fade-up" style="animation-delay: 0.12s">
                <div class="stat-card__icon"><flux:icon.calendar variant="mini" /></div>
                <p class="stat-card__label mt-4">Réservations à venir</p>
                <p class="stat-card__value text-blue-600">{{ $reserved }}</p>
            </div>
            <div class="stat-card animate-fade-up" style="animation-delay: 0.18s">
                <div class="stat-card__icon"><flux:icon.cube variant="mini" /></div>
                <p class="stat-card__label mt-4">Stock bas</p>
                <p class="stat-card__value {{ $lowStock > 0 ? 'text-amber-600' : 'text-zinc-400' }}">{{ $lowStock }}</p>
            </div>
        </div>

        @if ($lateCount > 0)
            <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-rose-800">{{ $lateCount }} location(s) en retard</span>
                    @can('rentals.view')
                        <a href="{{ route('rentals.index') }}" class="text-xs font-semibold text-rose-700 hover:underline" wire:navigate>Voir</a>
                    @endcan
                </div>
                <div class="mt-2 divide-y divide-rose-100">
                    @foreach ($lateRentals as $late)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-rose-800">{{ $late->customer->full_name }} · <span class="font-mono">{{ $late->reference }}</span></span>
                            <span class="text-rose-700">{{ $late->end_date?->format('d/m/Y') }} ({{ $late->end_date->startOfDay()->diffInDays(now()->startOfDay()) }} j de retard)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-6 stagger {{ $canSeeFinance ? 'lg:grid-cols-2' : '' }}">
            @if ($canSeeFinance)
                <div class="card card-pad animate-fade-up">
                    <h2 class="section-title">Évolution du chiffre d'affaires (12 mois)</h2>
                    @if (! empty($chartRevenue) && array_sum($chartRevenue) > 0)
                        <div class="mt-4 rounded-xl bg-gradient-to-br from-brand-50/40 to-transparent p-3">
                            <canvas id="revenueChart" height="120"></canvas>
                        </div>
                    @else
                        <div class="mt-4 flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-zinc-200 py-10">
                            <div class="skeleton h-28 w-full rounded-xl"></div>
                            <p class="text-xs text-zinc-400">Aucune donnée de chiffre d'affaires pour le moment.</p>
                        </div>
                    @endif
                </div>
            @endif
            <div class="card card-pad animate-fade-up">
                <h2 class="section-title">Articles les plus loués</h2>
                @if (! empty($topProductQty) && array_sum($topProductQty) > 0)
                    <div class="mt-4 rounded-xl bg-gradient-to-br from-brand-50/40 to-transparent p-3">
                        <canvas id="topProductsChart" height="120"></canvas>
                    </div>
                @else
                    <div class="mt-4 flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-zinc-200 py-10">
                        <div class="skeleton h-28 w-full rounded-xl"></div>
                        <p class="text-xs text-zinc-400">Aucun article loué pour le moment.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 stagger {{ $canSeeFinance ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }}">
            @if ($canSeeFinance)
                <div class="card card-pad">
                    <p class="text-xs text-zinc-500">Revenus packs</p>
                    <p class="mt-1 text-xl font-semibold text-zinc-900">{{ money($packRevenue) }}</p>
                </div>
            @endif
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Packs actuellement loués</p>
                <p class="mt-1 text-xl font-semibold text-emerald-600">{{ $packsActive }}</p>
            </div>
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Packs réservés</p>
                <p class="mt-1 text-xl font-semibold text-blue-600">{{ $packsReserved }}</p>
            </div>
            @if ($canSeeFinance)
                <div class="card card-pad">
                    <p class="text-xs text-zinc-500">Économie accordée</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-700">{{ money($packSavings) }}</p>
                </div>
            @endif
            <div class="card card-pad">
                <p class="text-xs text-zinc-500">Articles dans packs</p>
                <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $topPackProducts->sum('used_qty') }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3 stagger">
            <div class="card card-pad lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">Retours à venir (3 jours)</h2>
                    @can('rentals.view')
                        <a href="{{ route('rentals.index') }}" class="text-xs text-brand-700 hover:underline" wire:navigate>Tout voir</a>
                    @endcan
                </div>
                <div class="mt-3 divide-y divide-zinc-100">
                    @forelse ($upcomingReturns as $rental)
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-800">{{ strtoupper(substr($rental->customer->first_name, 0, 1)) }}{{ strtoupper(substr($rental->customer->last_name, 0, 1)) }}</span>
                                <div>
                                    <p class="font-medium text-zinc-900">{{ $rental->customer->full_name }}</p>
                                    <p class="text-xs text-zinc-500 font-mono">{{ $rental->reference }} · {{ $rental->items->sum('quantity') }} article(s)</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs {{ $rental->end_date->isToday() ? 'font-semibold text-rose-600' : 'text-zinc-500' }}">{{ $rental->end_date?->format('d/m') }} {{ $rental->end_date->isToday() ? '(aujourd\'hui)' : '' }}</span>
                                <a href="{{ route('rentals.show', $rental) }}" class="rounded-lg border border-zinc-200 px-3 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>Voir</a>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-zinc-500">Aucun retour prévu dans les 3 prochains jours.</p>
                    @endforelse
                </div>
            </div>

            <div class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">Stock bas</h2>
                    @can('stock.manage')
                        <a href="{{ route('stock.index') }}" class="text-xs text-brand-700 hover:underline" wire:navigate>Gérer</a>
                    @endcan
                </div>
                <div class="mt-3 space-y-2">
                    @forelse ($lowStockProducts as $product)
                        <div class="flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2">
                            <span class="truncate text-sm font-medium text-zinc-800">{{ $product->name }}</span>
                            <span class="badge-red">Qty {{ $product->quantity }}</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-zinc-500">Aucun produit en stock bas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($canSeeFinance)
        <div class="card card-pad">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900">Derniers paiements</h2>
                @can('payments.view')
                    <a href="{{ route('payments.index') }}" class="text-xs text-brand-700 hover:underline" wire:navigate>Tout voir</a>
                @endcan
            </div>
            <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200/70">
                <table class="table-premium">
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($recentPayments as $payment)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="px-4 py-2.5 font-mono text-xs text-zinc-500">{{ $payment->reference }}</td>
                                <td class="px-4 py-2.5 text-zinc-700">{{ $payment->rental?->customer?->full_name }}</td>
                                <td class="px-4 py-2.5 text-zinc-500">{{ $payment->date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">+ {{ money($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-500">Aucun paiement récent.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Packs les plus loués</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($topPacks as $pack)
                        <div class="flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2 text-sm">
                            <div>
                                <p class="font-medium text-zinc-800">{{ $pack->pack_label }}</p>
                                <p class="text-xs text-zinc-500">{{ (int) $pack->rentals_count }} location(s)</p>
                            </div>
                            @if ($canSeeFinance)
                                <p class="font-semibold text-zinc-900">{{ money((int) $pack->revenue) }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-zinc-500">Aucune location de pack pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Articles les plus utilisés dans les packs</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($topPackProducts as $row)
                        <div class="flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2 text-sm">
                            <p class="font-medium text-zinc-800">{{ $row->product?->name ?? 'Article supprimé' }}</p>
                            <span class="badge-zinc">{{ (int) $row->used_qty }} utilisations</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-zinc-500">Aucune donnée pack.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (document.getElementById('revenueChart')) {
                    new Chart(document.getElementById('revenueChart'), {
                        type: 'line',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{ label: 'CA ({{ currency_symbol(store_currency()) }})', data: @json($chartRevenue), borderColor: '#1e3a5f', backgroundColor: 'rgba(30,58,95,0.1)', fill: true, tension: 0.3 }]
                        },
                        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                }
                if (document.getElementById('topProductsChart')) {
                    new Chart(document.getElementById('topProductsChart'), {
                        type: 'bar',
                        data: {
                            labels: @json($topProductLabels),
                            datasets: [{ label: 'Qté louée', data: @json($topProductQty), backgroundColor: '#2563eb' }]
                        },
                        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                }
            });
        </script>
    </div>
</div>
