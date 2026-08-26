<div>
    <div class="space-y-6">
        <div>
            <a href="{{ route('packs.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux packs</a>
            <h1 class="page-title">{{ $pack->name }}</h1>
            <p class="page-subtitle font-mono">{{ $pack->reference }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 card overflow-hidden">
                @if ($pack->primaryImage())
                    <img src="{{ Storage::disk('public')->url($pack->primaryImage()->path) }}" alt="{{ $pack->name }}" class="h-72 w-full object-cover" />
                @endif
                <div class="p-5">
                    <h2 class="text-sm font-semibold text-zinc-900">Composition</h2>
                    <div class="mt-3 divide-y divide-zinc-100 rounded-xl border border-zinc-200">
                        @foreach ($pack->items as $item)
                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                <div>
                                    <p class="font-medium text-zinc-800">{{ $item->displayLabel() }}</p>
                                    @if ($item->isCategoryBased())
                                        <span class="badge-blue">Au choix</span>
                                    @endif
                                    <p class="text-xs text-zinc-500">{{ $item->variant_hint ?: 'Standard' }}</p>
                                </div>
                                <span class="font-semibold">× {{ $item->quantity }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Prix</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500">Prix normal</dt><dd>{{ money($pack->normalPrice()) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">Prix pack</dt><dd class="font-semibold">{{ money($pack->finalPrice()) }}</dd></div>
                        <div class="flex justify-between text-emerald-700"><dt>Économie</dt><dd>{{ money($pack->savingAmount()) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">Caution</dt><dd>{{ money($pack->caution) }}</dd></div>
                    </dl>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Actions</h2>
                    <div class="mt-3 space-y-2">
                        <a href="{{ route('packs.edit', $pack) }}" wire:navigate class="block rounded-xl border border-zinc-300 px-4 py-2 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50">Modifier</a>
                        <a href="{{ route('rentals.create', ['pack' => $pack->id]) }}" wire:navigate class="block rounded-xl bg-brand-800 px-4 py-2 text-center text-sm font-medium text-white hover:bg-brand-700">Réserver ce pack</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

