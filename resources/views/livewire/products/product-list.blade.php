<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Articles</h1>
                <p class="page-subtitle">{{ $products->total() }} article(s) au total.</p>
            </div>
            <a href="{{ route('products.create') }}" class="btn btn-primary" wire:navigate>
                <flux:icon.plus variant="mini" /> Nouvel article
            </a>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="card p-4">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="relative">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Nom, référence, code-barres..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="categoryId" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="status" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-1 rounded-xl bg-zinc-100 p-1">
                    <button wire:click="setViewMode('cards')" class="flex-1 rounded-lg px-3 py-1.5 text-sm font-medium {{ $viewMode === 'cards' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500' }}">
                        <flux:icon.squares-2x2 class="inline size-4" /> Cartes
                    </button>
                    <button wire:click="setViewMode('table')" class="flex-1 rounded-lg px-3 py-1.5 text-sm font-medium {{ $viewMode === 'table' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500' }}">
                        <flux:icon.table-cells class="inline size-4" /> Tableau
                    </button>
                </div>
            </div>
        </div>

        @if ($viewMode === 'cards')
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    @php $img = $product->primaryImage(); @endphp
                    <div class="card group overflow-hidden transition hover:shadow-md">
                        <a href="{{ route('products.show', $product) }}" class="block" wire:navigate>
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
                                @if ($img)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->path) }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-zinc-300">
                                        <flux:icon.photo class="size-10" />
                                    </div>
                                @endif
                                <span class="absolute left-2 top-2 {{ \App\Models\Product::statusBadge($product->status) }}">{{ $statuses[$product->status] ?? $product->status }}</span>
                            </div>
                        </a>
                        <div class="p-4">
                            <a href="{{ route('products.show', $product) }}" wire:navigate>
                                <h3 class="truncate font-medium text-zinc-900 hover:text-brand-700">{{ $product->name }}</h3>
                            </a>
                            <p class="text-xs text-zinc-500">{{ $product->reference }} · {{ $product->category?->name ?? '—' }}</p>
                            <div class="mt-3 flex items-center justify-between">
                                <p class="font-semibold text-zinc-900">{{ money($product->rental_price) }}</p>
                                <span class="badge-zinc">Stock: {{ $product->freeNow() }}/{{ $product->quantity }}</span>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('products.show', $product) }}" class="flex-1 rounded-lg border border-zinc-200 px-3 py-1.5 text-center text-xs font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>Voir</a>
                                <a href="{{ route('products.edit', $product) }}" class="flex-1 rounded-lg bg-brand-800 px-3 py-1.5 text-center text-xs font-medium text-white hover:bg-brand-700" wire:navigate>Modifier</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full card p-12 text-center text-sm text-zinc-500">
                        Aucun article trouvé.
                    </div>
                @endforelse
            </div>
        @else
            <div class="card overflow-x-auto">
                <table class="table-premium">
                    <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Photo</th>
                            <th class="px-4 py-3">Référence</th>
                            <th class="px-4 py-3">Article</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Prix</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($products as $product)
                            @php $img = $product->primaryImage(); @endphp
                            <tr class="hover:bg-zinc-50/50">
                                <td class="px-4 py-2">
                                    @if ($img)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->path) }}" class="h-10 w-10 rounded-lg object-cover" />
                                    @else
                                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-300"><flux:icon.photo class="size-5" /></span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 font-mono text-xs text-zinc-500">{{ $product->reference }}</td>
                                <td class="px-4 py-2 font-medium text-zinc-900">
                                    <a href="{{ route('products.show', $product) }}" wire:navigate class="hover:text-brand-700">{{ $product->name }}</a>
                                </td>
                                <td class="px-4 py-2 text-zinc-500">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <span class="flex items-center gap-2">
                                        {{ $product->freeNow() }} / {{ $product->quantity }}
                                        <span class="flex gap-0.5">
                                            <button wire:click="recordStockMovement({{ $product->id }}, 'in')" class="rounded bg-emerald-50 px-1.5 text-emerald-600 hover:bg-emerald-100" title="Ajouter">+</button>
                                            <button wire:click="recordStockMovement({{ $product->id }}, 'out')" class="rounded bg-rose-50 px-1.5 text-rose-600 hover:bg-rose-100" title="Retirer">−</button>
                                        </span>
                                    </span>
                                </td>
                                <td class="px-4 py-2"><span class="{{ \App\Models\Product::statusBadge($product->status) }}">{{ $statuses[$product->status] ?? $product->status }}</span></td>
                                <td class="px-4 py-2 font-medium">{{ money($product->rental_price) }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('products.show', $product) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Voir" wire:navigate><flux:icon.eye variant="mini" /></a>
                                        <a href="{{ route('products.edit', $product) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Modifier" wire:navigate><flux:icon.pencil-square variant="mini" /></a>
                                        <button wire:click="duplicateProduct({{ $product->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100" title="Dupliquer"><flux:icon.document-duplicate variant="mini" /></button>
                                        <button wire:click="deleteProduct({{ $product->id }})" wire:confirm="Supprimer cet article ?" class="rounded-lg p-1.5 text-zinc-500 hover:bg-rose-50 hover:text-rose-600" title="Supprimer"><flux:icon.trash variant="mini" /></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-zinc-500">Aucun article trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div>
            {{ $products->links() }}
        </div>
    </div>
</div>
