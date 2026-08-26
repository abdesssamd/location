<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('customers.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux clients</a>
                <h1 class="page-title">{{ $customer->full_name }}</h1>
                <p class="page-subtitle">Client depuis le {{ $customer->created_at?->format('d/m/Y') }}</p>
            </div>
            <div class="flex gap-2">
                @if (Route::has('rentals.create'))
                    <a href="{{ route('rentals.create', ['customer' => $customer->id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700" wire:navigate>
                        <flux:icon.calendar-days variant="mini" /> Louer
                    </a>
                @endif
                <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>
                    <flux:icon.pencil-square variant="mini" /> Modifier
                </a>
            </div>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6">
                <div class="card card-pad">
                    <div class="flex items-center gap-4">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-lg font-semibold text-brand-800">{{ strtoupper(substr($customer->first_name, 0, 1)) }}{{ strtoupper(substr($customer->last_name, 0, 1)) }}</span>
                        <div>
                            <h2 class="font-semibold text-zinc-900">{{ $customer->full_name }}</h2>
                            @if ($customer->favorite)
                                <span class="badge-yellow"><flux:icon.star variant="mini" /> Favori</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Coordonnées</h2>
                    <dl class="mt-3 space-y-3 text-sm">
                        <div class="flex items-center gap-3"><flux:icon.phone variant="mini" class="text-zinc-400" /><span class="text-zinc-700">{{ $customer->phone }}</span></div>
                        @if ($customer->email)
                            <div class="flex items-center gap-3"><flux:icon.envelope variant="mini" class="text-zinc-400" /><a href="mailto:{{ $customer->email }}" class="text-zinc-700 hover:text-brand-700">{{ $customer->email }}</a></div>
                        @endif
                        @if ($customer->cin)
                            <div class="flex items-center gap-3"><flux:icon.identification variant="mini" class="text-zinc-400" /><span class="font-mono text-zinc-700">{{ $customer->cin }}</span></div>
                        @endif
                        @if ($customer->address)
                            <div class="flex items-center gap-3"><flux:icon.map-pin variant="mini" class="text-zinc-400" /><span class="text-zinc-700">{{ $customer->address }}</span></div>
                        @endif
                        @if ($customer->wilaya || $customer->commune)
                            <div class="flex items-center gap-3"><flux:icon.map-pin variant="mini" class="text-zinc-400" /><span class="text-zinc-700">{{ $customer->commune }}{{ $customer->commune && $customer->wilaya ? ', ' : '' }}{{ $customer->wilaya }}</span></div>
                        @endif
                        @if ($customer->phone_secondary)
                            <div class="flex items-center gap-3"><flux:icon.phone variant="mini" class="text-zinc-400" /><span class="text-zinc-700">{{ $customer->phone_secondary }}</span></div>
                        @endif
                        @if ($customer->birth_date)
                            <div class="flex items-center gap-3"><flux:icon.cake variant="mini" class="text-zinc-400" /><span class="text-zinc-700">{{ $customer->birth_date?->format('d/m/Y') }}</span></div>
                        @endif
                    </dl>
                </div>

                @if ($customer->notes)
                    <div class="card card-pad">
                        <h2 class="text-sm font-semibold text-zinc-900">Notes</h2>
                        <p class="mt-2 text-sm text-zinc-600">{{ $customer->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="card card-pad lg:col-span-2">
                <h2 class="text-sm font-semibold text-zinc-900">Historique des locations</h2>
                @if ($hasRentals && $rentals->isNotEmpty())
                    <div class="mt-3 divide-y divide-zinc-100">
                        @foreach ($rentals as $rental)
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="font-medium text-zinc-900">{{ $rental->reference }}</p>
                                    <p class="text-xs text-zinc-500">{{ $rental->start_date?->format('d/m/Y') }} → {{ $rental->end_date?->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="badge-zinc">{{ $rental->status }}</span>
                                    <span class="text-sm font-semibold">{{ money($rental->total_amount ?? 0) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-xl bg-zinc-50 py-8 text-center text-sm text-zinc-500">
                        Aucune location enregistrée pour ce client.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
