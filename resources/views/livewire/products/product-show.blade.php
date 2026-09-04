<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('products.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux articles</a>
                <h1 class="page-title">{{ $product->name }}</h1>
                <p class="page-subtitle font-mono text-xs">{{ $product->reference }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>
                    <flux:icon.pencil-square variant="mini" /> Modifier
                </a>
                @if (Route::has('rentals.create'))
                    <a href="{{ route('rentals.create', ['product' => $product->id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700" wire:navigate>
                        <flux:icon.calendar-days variant="mini" /> Réserver
                    </a>
                @endif
            </div>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card overflow-hidden lg:col-span-2">
                <div class="relative aspect-[4/3] bg-zinc-100">
                    @if ($selectedImage)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($selectedImage->path) }}" class="h-full w-full object-cover" />
                    @else
                        <div class="flex h-full w-full items-center justify-center text-zinc-300"><flux:icon.photo class="size-16" /></div>
                    @endif
                    <span class="absolute left-3 top-3 {{ \App\Models\Product::statusBadge($product->status) }}">{{ $statuses[$product->status] ?? $product->status }}</span>
                </div>
                @if ($product->images->count() > 1)
                    <div class="flex gap-2 overflow-x-auto p-3">
                        @foreach ($product->images as $img)
                            <button wire:click="selectImage({{ $img->id }})" class="shrink-0 overflow-hidden rounded-lg ring-2 transition {{ $selectedImage?->id === $img->id ? 'ring-brand-600' : 'ring-transparent hover:ring-zinc-300' }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->path) }}" class="h-16 w-16 object-cover" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Prix</h2>
                    <dl class="mt-3 space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500">Location</dt><dd class="text-lg font-semibold text-zinc-900">{{ money($product->rental_price) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">Caution</dt><dd>{{ money($product->caution_price) }}</dd></div>
                        @if ($product->sale_price !== null)
                            <div class="flex justify-between"><dt class="text-zinc-500">Vente</dt><dd>{{ money($product->sale_price) }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Caractéristiques</h2>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-xs text-zinc-500">Catégorie</dt><dd class="font-medium">{{ $product->category?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Marque</dt><dd class="font-medium">{{ $product->brand ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Taille</dt><dd class="font-medium">{{ $product->size ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Couleur</dt><dd class="font-medium">{{ $product->color ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Matière</dt><dd class="font-medium">{{ $product->material ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Stock disponible / total</dt><dd class="font-medium">{{ $product->freeNow() }} / {{ $product->quantity }}</dd></div>
                    </dl>
                </div>

                @can('stock.manage')
                <div class="card card-pad flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900">Statut du stock</h2>
                        <p class="mt-1 text-xs text-zinc-500">Changer l'état de l'article.</p>
                    </div>
                    <select wire:change="changeStatus($event.target.value)" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($product->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endcan
            </div>
        </div>

        <div class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900">Disponibilité</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">Jours libres pour cet article. Cliquez sur un jour pour lancer une réservation.</p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-zinc-500">Quantité</label>
                    <input wire:model.live="planningQuantity" type="number" min="1" max="{{ max(1, $product->quantity) }}"
                        class="w-16 rounded-lg border border-zinc-300 px-2 py-1 text-center text-sm focus:border-brand-600 focus:outline-none" />
                    <button type="button" wire:click="previousMonth" class="rounded-lg border border-zinc-300 p-1.5 text-zinc-600 hover:bg-zinc-50" title="Mois précédent">
                        <flux:icon.chevron-left variant="mini" />
                    </button>
                    <button type="button" wire:click="goToToday" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Aujourd'hui</button>
                    <button type="button" wire:click="nextMonth" class="rounded-lg border border-zinc-300 p-1.5 text-zinc-600 hover:bg-zinc-50" title="Mois suivant">
                        <flux:icon.chevron-right variant="mini" />
                    </button>
                </div>
            </div>

            <p class="mt-4 text-center text-sm font-medium capitalize text-zinc-800">{{ $planningLabel }}</p>

            <div class="mt-3 grid grid-cols-7 gap-1 text-center">
                @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName)
                    <div class="pb-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ $dayName }}</div>
                @endforeach

                @for ($i = 0; $i < $leadingBlanks; $i++)
                    <div></div>
                @endfor

                @foreach ($planningDays as $day)
                    @php
                        $classes = match (true) {
                            $day['is_past'] => 'border-zinc-100 bg-zinc-50 text-zinc-300',
                            $day['is_free'] => 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:border-emerald-400',
                            default => 'border-rose-200 bg-rose-50 text-rose-700',
                        };
                    @endphp

                    @if ($day['is_free'] && ! $day['is_past'] && auth()->user()->can('rentals.create'))
                        <a href="{{ route('rentals.create', ['start' => $day['date'], 'product' => $product->id]) }}" wire:navigate
                           class="rounded-lg border py-1.5 transition {{ $classes }} {{ $day['is_today'] ? 'ring-2 ring-brand-600' : '' }}"
                           title="{{ $day['free'] }} exemplaire(s) libre(s)">
                            <span class="block text-sm font-medium">{{ $day['day'] }}</span>
                            <span class="block text-[10px]">{{ $day['free'] }}</span>
                        </a>
                    @else
                        <div class="rounded-lg border py-1.5 {{ $classes }} {{ $day['is_today'] ? 'ring-2 ring-brand-600' : '' }}"
                             title="{{ $day['is_past'] ? 'Date passée' : $day['free'].' exemplaire(s) libre(s)' }}">
                            <span class="block text-sm font-medium">{{ $day['day'] }}</span>
                            <span class="block text-[10px]">{{ $day['is_past'] ? '—' : $day['free'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-zinc-500">
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded border border-emerald-200 bg-emerald-50"></span> Libre
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded border border-rose-200 bg-rose-50"></span> Complet
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded border border-zinc-100 bg-zinc-50"></span> Passé
                </span>
                <span class="text-zinc-400">Le chiffre indique le nombre d'exemplaires encore libres.</span>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">QR Code</h2>
                <div class="mt-3 flex items-center gap-4">
                    <div class="rounded-xl bg-white p-2 ring-1 ring-zinc-200">
                        {!! QrCode::size(140)->generate($product->qr_code ?? route('products.scan', $product, false)) !!}
                    </div>
                    <div class="text-sm text-zinc-500">
                        <p class="font-medium text-zinc-900">Scannez pour retrouver cet article.</p>
                        <p class="mt-1">Code-barres : <span class="font-mono">{{ $product->barcode }}</span></p>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Historique de stock</h2>
                <div class="mt-3 divide-y divide-zinc-100">
                    @forelse ($product->stockMovements->take(20) as $movement)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <span class="{{ $movement->quantity > 0 ? 'badge-green' : ($movement->quantity < 0 ? 'badge-red' : 'badge-zinc') }}">
                                    {{ \App\Models\StockMovement::typeLabels()[$movement->type] ?? $movement->type }}
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                                <span class="ml-2 text-xs text-zinc-500">{{ $movement->reason }}</span>
                            </div>
                            <span class="text-xs text-zinc-400">{{ $movement->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-zinc-500">Aucun mouvement de stock.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
