<x-layouts.app>
    <div class="space-y-6" x-data>
        <div>
            <h1 class="page-title">Mon abonnement</h1>
            <p class="page-subtitle">Plan, consommation et paiements de votre magasin.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif
        @if (session('warning'))
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ session('warning') }}
            </div>
        @endif

        @if ($service->inGrace())
            <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                Période de grâce en cours jusqu'au {{ $service->graceEndsAt()?->format('d/m/Y') }}. Renouvelez pour éviter la suspension.
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Carte plan actuel --}}
            <div class="card card-pad lg:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-zinc-500">Plan actuel</p>
                        <h2 class="mt-1 text-2xl font-bold text-zinc-900">{{ $plan?->name ?? 'Aucun plan' }}</h2>
                        @if ($plan)
                            <p class="text-sm text-zinc-500">{{ number_format($plan->price, 0, ',', ' ') }} {{ $plan->billing_period === 'yearly' ? 'DA / an' : 'DA / mois' }}</p>
                        @endif
                    </div>
                    <div class="space-y-2 text-right">
                        @if ($subscription)
                            <span class="{{ \App\Models\Subscription::statusBadge($service->status()) }}">{{ \App\Models\Subscription::statusLabels()[$service->status()] ?? $service->status() }}</span>
                        @else
                            <span class="badge-red">Aucun abonnement</span>
                        @endif
                        @if ($service->endsAt())
                            <p class="text-sm text-zinc-600">Expire le <strong>{{ $service->endsAt()?->format('d/m/Y') }}</strong></p>
                            @if ($service->isActive())
                                <p class="text-xs text-zinc-500">{{ $service->daysRemaining() }} jour(s) restant(s)</p>
                            @endif
                        @endif
                    </div>
                </div>

                @if ($plan)
                    <div class="mt-6 space-y-4">
                        @foreach ([
                            'Articles' => [$usage['products'], $plan->max_products],
                            'Clients' => [$usage['customers'], $plan->max_customers],
                            'Utilisateurs' => [$usage['users'], $plan->max_users],
                            'Locations ce mois-ci' => [$usage['rentals'], $plan->max_rentals_per_month],
                        ] as $label => [$value, $max])
                            @php $percent = $max ? min(100, (int) round($value / $max * 100)) : min(100, $value * 2); @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-zinc-700">{{ $label }}</span>
                                    <span class="text-zinc-500">{{ $value }} / {{ $max ?? '∞' }}</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100">
                                    <div class="h-full rounded-full transition-all {{ $percent >= 100 ? 'bg-rose-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('subscription.plans') }}" class="rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Renouveler / Changer de plan</a>
                </div>
            </div>

            {{-- Token --}}
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Token du magasin</h2>
                <p class="mt-1 text-xs text-zinc-500">Identifiant unique du magasin (ne le partagez pas). Seul un aperçu est conservé : la valeur complète n'est affichée qu'à la génération.</p>
                <p class="mt-3 break-all rounded-xl bg-zinc-50 px-3 py-2 font-mono text-sm text-zinc-800">{{ $token?->token ?? '—' }}</p>
                @if ($token?->last_used_at)
                    <p class="mt-2 text-xs text-zinc-500">Dernier appel API : {{ $token->last_used_at->format('d/m/Y H:i') }}{{ $token->last_ip ? ' depuis '.$token->last_ip : '' }}</p>
                @endif
                <p class="mt-2 text-xs text-zinc-500">Token perdu ? Demandez une régénération à l'administrateur : l'ancien devient immédiatement invalide.</p>
                @if ($token?->status !== 'active')
                    <p class="mt-2 text-xs font-medium text-rose-600">Token désactivé — contactez l'administrateur.</p>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Paiement hors ligne --}}
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Payer hors ligne</h2>
                <p class="mt-1 text-xs text-zinc-500">BaridiMob, CCP, virement… Envoyez la preuve, l'administrateur active votre abonnement.</p>
                <form method="POST" action="{{ route('subscription.pay') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select name="plan_id" required class="rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                            <option value="">— Choisir un plan —</option>
                            @foreach (\App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} — {{ money($p->price) }}</option>
                            @endforeach
                        </select>
                        <select name="method" required class="rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                            @foreach ($methods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input name="reference" placeholder="Référence de la transaction" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm" />
                    <textarea name="notes" rows="2" placeholder="Notes (optionnel)" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm"></textarea>
                    <input type="file" name="proof" accept="image/*" class="w-full text-sm text-zinc-600" />
                    <button type="submit" class="rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Envoyer la preuve</button>
                </form>
            </div>

            {{-- Historique paiements --}}
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Historique des paiements</h2>
                <div class="mt-4 divide-y divide-zinc-100">
                    @forelse ($payments as $payment)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium text-zinc-900">{{ $payment->plan?->name }} — {{ money($payment->amount) }}</p>
                                <p class="text-xs text-zinc-500">{{ $payment->reference }} · {{ $payment->created_at?->format('d/m/Y') }} · {{ \App\Models\SubscriptionPayment::METHODS[$payment->method] ?? $payment->method }}</p>
                            </div>
                            <span class="{{ \App\Models\SubscriptionPayment::statusBadge($payment->status) }}">{{ \App\Models\SubscriptionPayment::statusLabels()[$payment->status] }}</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-zinc-500">Aucun paiement enregistré.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
