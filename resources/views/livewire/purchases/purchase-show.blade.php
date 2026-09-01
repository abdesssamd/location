<div>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('purchases.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux achats</a>
                <h1 class="page-title font-mono">{{ $purchase->reference }}</h1>
                <p class="page-subtitle">Achat du {{ $purchase->date->format('d/m/Y') }} · <span class="{{ \App\Models\Purchase::statusBadge($purchase->status) }}">{{ \App\Models\Purchase::statusLabels()[$purchase->status] ?? $purchase->status }}</span></p>
            </div>
            @can('cancel', $purchase)
                @if ($purchase->status === \App\Models\Purchase::STATUS_RECEIVED)
                    <button type="button" wire:click="cancel" wire:confirm="Annuler cet achat et retirer le stock reçu ?" class="inline-flex items-center gap-2 rounded-xl border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">
                        <flux:icon.x-mark variant="mini" /> Annuler l'achat
                    </button>
                @endif
            @endcan
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Articles reçus</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($purchase->items as $item)
                            <div class="flex items-center justify-between rounded-xl border border-zinc-100 px-3 py-2 text-sm">
                                <div>
                                    <p class="font-medium text-zinc-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-zinc-500">{{ money($item->unit_cost) }} / unité</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-zinc-500">× {{ $item->quantity }}</p>
                                    <p class="font-semibold text-zinc-900">{{ money($item->line_total) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($purchase->notes)
                    <div class="card card-pad">
                        <h2 class="text-sm font-semibold text-zinc-900">Notes</h2>
                        <p class="mt-2 text-sm text-zinc-600">{{ $purchase->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="card card-pad space-y-3">
                    <h2 class="text-sm font-semibold text-zinc-900">Fournisseur</h2>
                    @if ($purchase->supplier)
                        <p class="font-medium text-zinc-900">{{ $purchase->supplier->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $purchase->supplier->phone }}</p>
                    @else
                        <p class="text-sm text-zinc-400">Aucun fournisseur enregistré.</p>
                    @endif
                </div>

                <div class="card card-pad space-y-2">
                    <h2 class="text-sm font-semibold text-zinc-900">Montants</h2>
                    <div class="flex items-center justify-between border-t border-zinc-100 pt-2 text-base font-semibold text-zinc-900">
                        <span>Total</span>
                        <span class="tabular-nums">{{ money($purchase->total) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">Payé</span>
                        <span class="tabular-nums text-emerald-600">{{ money($purchase->paid_amount) }}</span>
                    </div>
                    @if ($purchase->remaining > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">Reste dû au fournisseur</span>
                            <span class="tabular-nums text-rose-600">{{ money($purchase->remaining) }}</span>
                        </div>
                    @endif
                </div>

                @if ($purchase->user)
                    <div class="card card-pad">
                        <p class="text-xs text-zinc-500">Réceptionné par</p>
                        <p class="text-sm font-medium text-zinc-900">{{ $purchase->user->name }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
