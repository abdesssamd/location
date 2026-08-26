<div class="mx-auto max-w-md space-y-6">
    <div>
        <h1 class="page-title">Scanner</h1>
        <p class="page-subtitle">Saisissez une référence ou un code-barres.</p>
    </div>

    <div class="card p-4">
        <div class="relative">
            <flux:icon.qr-code class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
            <input wire:model.live.debounce.200ms="query" placeholder="Référence, code-barres, nom..." autofocus class="w-full rounded-xl border border-zinc-300 py-2.5 pl-9 pr-3 text-base focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
        </div>
        <p class="mt-2 text-center text-xs text-zinc-400">Saisie clavier ou scan avec un lecteur USB.</p>
    </div>

    @if (strlen($query) >= 2)
        @if ($result)
            <div class="card overflow-hidden">
                @if ($img = $result->primaryImage())
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->path) }}" class="aspect-[4/3] w-full object-cover" />
                @else
                    <div class="flex aspect-[4/3] w-full items-center justify-center bg-zinc-100 text-zinc-300"><flux:icon.photo class="size-12" /></div>
                @endif
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-900">{{ $result->name }}</h2>
                            <p class="font-mono text-xs text-zinc-500">{{ $result->reference }}</p>
                        </div>
                        <span class="{{ \App\Models\Product::statusBadge($result->status) }}">{{ $statuses[$result->status] ?? $result->status }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-3 text-center text-sm">
                        <div class="rounded-xl bg-zinc-50 py-2"><dt class="text-xs text-zinc-500">Stock dispo/total</dt><dd class="font-semibold">{{ $result->freeNow() }} / {{ $result->quantity }}</dd></div>
                        <div class="rounded-xl bg-zinc-50 py-2"><dt class="text-xs text-zinc-500">Location</dt><dd class="font-semibold">{{ money($result->rental_price) }}</dd></div>
                        <div class="rounded-xl bg-zinc-50 py-2"><dt class="text-xs text-zinc-500">Caution</dt><dd class="font-semibold">{{ money($result->caution_price) }}</dd></div>
                    </dl>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('products.show', $result) }}" class="flex-1 rounded-xl bg-brand-800 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-brand-700" wire:navigate>Fiche article</a>
                        @if (Route::has('rentals.create'))
                            <a href="{{ route('rentals.create', ['product' => $result->id]) }}" class="flex-1 rounded-xl border border-zinc-300 px-4 py-2.5 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>Louer</a>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="card p-8 text-center">
                <flux:icon.magnifying-glass class="mx-auto size-8 text-zinc-300" />
                <p class="mt-2 text-sm text-zinc-500">Aucun article trouvé pour « {{ $query }} ».</p>
            </div>
        @endif
    @endif
</div>
