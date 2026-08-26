<x-layouts.app title="Scan — {{ $product->name }}">
    <div class="mx-auto max-w-lg space-y-6">
        <div class="card overflow-hidden">
            <div class="relative aspect-[4/3] bg-zinc-100">
                @if ($img = $product->primaryImage())
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->path) }}" class="h-full w-full object-cover" />
                @else
                    <div class="flex h-full w-full items-center justify-center text-zinc-300"><flux:icon.photo class="size-14" /></div>
                @endif
                <span class="absolute left-3 top-3 {{ \App\Models\Product::statusBadge($product->status) }}">{{ \App\Models\Product::statusLabels()[$product->status] ?? $product->status }}</span>
            </div>
            <div class="p-5">
                <h1 class="text-lg font-semibold text-zinc-900">{{ $product->name }}</h1>
                <p class="font-mono text-xs text-zinc-500">{{ $product->reference }}</p>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-zinc-500">Prix location</dt><dd class="font-semibold">{{ money($product->rental_price) }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Caution</dt><dd>{{ money($product->caution_price) }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Stock</dt><dd>{{ $product->quantity }}</dd></div>
                    <div><dt class="text-xs text-zinc-500">Catégorie</dt><dd>{{ $product->category?->name ?? '—' }}</dd></div>
                </dl>

                <div class="mt-5 flex gap-2">
                    <a href="{{ route('products.show', $product) }}" class="flex-1 rounded-xl bg-brand-800 px-4 py-2 text-center text-sm font-medium text-white hover:bg-brand-700" wire:navigate>Voir la fiche</a>
                    <a href="{{ route('products.index') }}" class="flex-1 rounded-xl border border-zinc-300 px-4 py-2 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>Scanner un autre</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
