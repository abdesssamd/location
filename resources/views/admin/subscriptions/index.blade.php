<x-layouts.admin title="Abonnements">
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Abonnements SaaS</h1>
            <p class="page-subtitle">Paiements en attente, abonnements et revenus de la plateforme.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash status="error" :type="session('error')" />
        @endif

        {{-- Statistiques --}}
        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                ['Magasins actifs', $stats['active_stores'], 'text-emerald-600'],
                ['Expirés', $stats['expired'], 'text-rose-600'],
                ['PRO', $stats['pro'], 'text-brand-800'],
                ['PREMIUM', $stats['premium'], 'text-violet-600'],
                ['Revenus', number_format($stats['revenue'], 0, ',', ' ').' DA', 'text-emerald-600'],
                ['Expirent < 7j', $stats['expiring_soon'], 'text-amber-600'],
            ] as [$label, $value, $color])
                <div class="card card-pad">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Paiements en attente --}}
        <div class="card overflow-hidden">
            <div class="border-b border-zinc-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-zinc-900">Paiements en attente ({{ $pendingPayments->count() }})</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($pendingPayments as $payment)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-900">{{ $payment->store?->name }} — {{ $payment->plan?->name }} ({{ money($payment->amount) }})</p>
                            <p class="text-xs text-zinc-500">{{ $payment->reference }} · {{ \App\Models\SubscriptionPayment::METHODS[$payment->method] ?? $payment->method }} · {{ $payment->created_at?->format('d/m/Y H:i') }}</p>
                            @if ($payment->notes)
                                <p class="mt-1 text-xs text-zinc-500">{{ $payment->notes }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($payment->proof_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path) }}" target="_blank" class="rounded-xl border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Preuve</a>
                            @endif
                            <form method="POST" action="{{ route('admin.subscriptions.approve', $payment) }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-500">Approuver</button>
                            </form>
                            <form method="POST" action="{{ route('admin.subscriptions.reject', $payment) }}">
                                @csrf
                                <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50">Refuser</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="p-10 text-center text-sm text-zinc-500">Aucun paiement en attente.</p>
                @endforelse
            </div>
        </div>

        {{-- Abonnements --}}
        <div class="card overflow-hidden">
            <div class="border-b border-zinc-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-zinc-900">Abonnements des magasins</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Magasin</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Début</th>
                            <th class="px-4 py-3">Expiration</th>
                            <th class="px-4 py-3">Jours restants</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($subscriptions as $sub)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="px-4 py-2.5 font-medium text-zinc-900">{{ $sub->store?->name }}</td>
                                <td class="px-4 py-2.5">{{ $sub->plan?->name }}</td>
                                <td class="px-4 py-2.5"><span class="{{ \App\Models\Subscription::statusBadge($sub->status) }}">{{ \App\Models\Subscription::statusLabels()[$sub->status] ?? $sub->status }}</span></td>
                                <td class="px-4 py-2.5 text-zinc-500">{{ $sub->starts_at?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-zinc-500">{{ $sub->ends_at?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($sub->ends_at && $sub->ends_at->isFuture())
                                        <span class="text-zinc-700">{{ now()->diffInDays($sub->ends_at, false) }} j</span>
                                    @elseif ($sub->ends_at)
                                        <span class="font-medium text-rose-600">Expiré</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Aucun abonnement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 p-4">{{ $subscriptions->links() }}</div>
        </div>
    </div>
</x-layouts.admin>
