<x-layouts.app>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Plans d'abonnement</h1>
            <p class="page-subtitle">Choisissez le plan adapté à votre magasin. Activation après validation du paiement.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="card card-pad relative flex flex-col {{ $plan->is_popular ? 'ring-2 ring-brand-800' : '' }}">
                    @if ($plan->is_popular)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-800 px-3 py-1 text-xs font-semibold text-white">Populaire</span>
                    @endif

                    <h2 class="text-lg font-bold text-zinc-900">{{ $plan->name }}</h2>
                    <p class="mt-1 text-3xl font-extrabold text-zinc-900">{{ number_format($plan->price, 0, ',', ' ') }} <span class="text-sm font-medium text-zinc-500">DA / {{ $plan->billing_period === 'yearly' ? 'an' : 'mois' }}</span></p>
                    @if ($plan->description)
                        <p class="mt-2 text-sm text-zinc-500">{{ $plan->description }}</p>
                    @endif

                    <ul class="mt-4 flex-1 space-y-2 text-sm text-zinc-700">
                        <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="size-4 text-emerald-600" /> {{ $plan->limitLabel($plan->max_users) }} utilisateur(s)</li>
                        <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="size-4 text-emerald-600" /> {{ $plan->limitLabel($plan->max_products) }} articles</li>
                        <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="size-4 text-emerald-600" /> {{ $plan->limitLabel($plan->max_customers) }} clients</li>
                        @foreach ($plan->features ?? [] as $feature)
                            <li class="flex items-center gap-2"><flux:icon.check-circle variant="mini" class="size-4 text-emerald-600" /> {{ $featureLabels[$feature] ?? $feature }}</li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('subscription.subscribe', $plan) }}" class="mt-5">
                        @csrf
                        <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $plan->is_popular ? 'bg-brand-800 text-white hover:bg-brand-700' : 'border border-zinc-300 text-zinc-700 hover:bg-zinc-50' }}">
                            Choisir {{ $plan->name }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-semibold text-zinc-900">Comparaison détaillée</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Fonctionnalité</th>
                            @foreach ($plans as $plan)
                                <th class="px-3 py-2 text-center">{{ $plan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($featureLabels as $key => $label)
                            <tr>
                                <td class="px-3 py-2 text-zinc-700">{{ $label }}</td>
                                @foreach ($plans as $plan)
                                    <td class="px-3 py-2 text-center">
                                        @if (in_array($key, $plan->features ?? []))
                                            <flux:icon.check-circle variant="mini" class="mx-auto size-4 text-emerald-600" />
                                        @else
                                            <flux:icon.x-mark variant="mini" class="mx-auto size-4 text-zinc-300" />
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>