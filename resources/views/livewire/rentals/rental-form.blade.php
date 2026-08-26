<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('rentals.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux locations</a>
                <h1 class="page-title">{{ $rental?->exists ? 'Modifier la réservation' : 'Nouvelle réservation' }}</h1>
            </div>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Client</h2>
                    <div class="mt-3">
                        @if ($selectedCustomer)
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-800">{{ strtoupper(substr($selectedCustomer->first_name, 0, 1)) }}{{ strtoupper(substr($selectedCustomer->last_name, 0, 1)) }}</span>
                                    <div>
                                        <p class="font-medium text-zinc-900">{{ $selectedCustomer->full_name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $selectedCustomer->phone }}</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('customer_id', null)" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100"><flux:icon.x-mark variant="mini" /></button>
                            </div>
                        @else
                            <div class="relative">
                                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                                <input wire:model.live.debounce.250ms="customer_search" placeholder="Rechercher un client par nom ou téléphone..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <p class="text-xs text-zinc-400">Recherche instantanée</p>
                                <button type="button" wire:click="openCustomerModal" class="inline-flex items-center gap-1 rounded-lg border border-brand-300 px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50">
                                    <flux:icon.plus variant="mini" /> Nouveau client
                                </button>
                            </div>
                            @if ($customer_search && $customers->isNotEmpty())
                                <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200">
                                    @foreach ($customers as $customer)
                                        <button type="button" wire:click="selectCustomer({{ $customer->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                            <span class="text-zinc-700">{{ $customer->full_name }}</span>
                                            <span class="text-xs text-zinc-400">{{ $customer->phone }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @if ($customer_search && $customers->isEmpty())
                                <p class="mt-2 text-xs text-zinc-500">Aucun client trouvé. <button type="button" wire:click="openCustomerModal" class="text-brand-700 underline">Créer « {{ $customer_search }} »</button></p>
                            @endif
                        @endif

                        @if ($showCustomerModal)
                            <div class="mt-3 rounded-xl border border-brand-200 bg-brand-50/40 p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-zinc-900">Nouveau client</p>
                                    <button type="button" wire:click="closeCustomerModal" class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100"><flux:icon.x-mark variant="mini" /></button>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Prénom *</label>
                                        <input wire:model="new_first_name" placeholder="Prénom" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                        @error('new_first_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Nom *</label>
                                        <input wire:model="new_last_name" placeholder="Nom" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                        @error('new_last_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Téléphone *</label>
                                        <input wire:model="new_phone" placeholder="0550..." class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                        @error('new_phone') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Téléphone 2</label>
                                        <input wire:model="new_phone_secondary" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Email</label>
                                        <input wire:model="new_email" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                        @error('new_email') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Wilaya</label>
                                        <input wire:model="new_wilaya" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs text-zinc-600">Commune</label>
                                        <input wire:model="new_commune" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" wire:click="closeCustomerModal" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50">Annuler</button>
                                    <button type="button" wire:click="createCustomer" class="rounded-lg bg-brand-800 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-700">Créer le client</button>
                                </div>
                            </div>
                        @endif
                        @error('customer_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="card card-pad">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-zinc-900">Composition</h2>
                        <div class="inline-flex rounded-xl border border-zinc-300 bg-zinc-50 p-1">
                            <button type="button" wire:click="$set('mode', 'article')" @class([
                                'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                'bg-white text-zinc-900 shadow-sm' => $mode === 'article',
                                'text-zinc-500 hover:text-zinc-700' => $mode !== 'article',
                            ])>
                                Article
                            </button>
                            <button type="button" wire:click="$set('mode', 'pack')" @class([
                                'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                'bg-white text-zinc-900 shadow-sm' => $mode === 'pack',
                                'text-zinc-500 hover:text-zinc-700' => $mode !== 'pack',
                            ])>
                                Pack
                            </button>
                        </div>
                    </div>

                    @if ($mode === 'article')
                        <div class="mt-3">
                            <div class="relative">
                                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                                <input wire:model.live.debounce.250ms="product_search" placeholder="Rechercher un article par nom ou référence..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            @if ($product_search && $products->isNotEmpty())
                                <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200">
                                    @foreach ($products as $product)
                                        @php $free = $productFree[$product->id] ?? $product->quantity; @endphp
                                        <button type="button" wire:click="addProduct({{ $product->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                            <div>
                                                <p class="font-medium text-zinc-800">{{ $product->name }}</p>
                                                <p class="text-xs text-zinc-400 font-mono">{{ $product->reference }} · Dispo: {{ $free }}/{{ $product->quantity }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if ($free < 1)
                                                    <span class="text-xs text-rose-600">Indisponible</span>
                                                @else
                                                    <span class="text-xs text-emerald-600">En stock</span>
                                                @endif
                                                <span class="text-sm font-semibold text-brand-800">{{ money($product->rental_price) }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="mt-3">
                            <div class="relative">
                                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                                <input wire:model.live.debounce.250ms="pack_search" placeholder="Rechercher un pack par nom ou référence..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            @if ($pack_search && $packs->isNotEmpty())
                                <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200">
                                    @foreach ($packs as $pack)
                                        @php
                                            $normal = $pack->normalPrice();
                                            $final = $pack->finalPrice();
                                        @endphp
                                        <button type="button" wire:click="addPack({{ $pack->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                            <div>
                                                <p class="font-medium text-zinc-800">{{ $pack->name }}</p>
                                                <p class="text-xs text-zinc-400 font-mono">{{ $pack->reference }} · {{ $pack->items->sum('quantity') }} article(s)</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-zinc-400 line-through">{{ money($normal) }}</p>
                                                <p class="text-sm font-semibold text-brand-800">{{ money($final) }}</p>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @if (count($packs))
                        <div class="mt-4 space-y-3">
                            @foreach ($packs as $packIndex => $selectedPack)
                                @php
                                    $pack = $pickedPacks[$selectedPack['pack_id']] ?? null;
                                    $availability = collect($packAvailability)->firstWhere('index', $packIndex);
                                @endphp
                                @if ($pack)
                                    <div class="rounded-xl border border-zinc-200 p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-zinc-900">{{ $pack->name }}</p>
                                                <p class="text-xs text-zinc-500">{{ $pack->reference }}</p>
                                            </div>
                                            <button type="button" wire:click="removePack({{ $packIndex }})" class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600"><flux:icon.trash variant="mini" /></button>
                                        </div>

                                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                                            <div class="space-y-1">
                                                <label class="text-xs uppercase tracking-wide text-zinc-500">Quantité de packs</label>
                                                <input type="number" min="1" wire:model="packs.{{ $packIndex }}.quantity" class="w-24 rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none" />
                                            </div>
                                            <div class="rounded-lg bg-zinc-50 px-3 py-2 text-xs">
                                                <div class="flex justify-between"><span>Prix normal</span><span>{{ number_format($pack->normalPrice() * max(1, (int) $selectedPack['quantity']), 0, ',', ' ') }} DA</span></div>
                                                <div class="flex justify-between font-semibold"><span>Prix pack</span><span>{{ number_format($pack->finalPrice() * max(1, (int) $selectedPack['quantity']), 0, ',', ' ') }} DA</span></div>
                                                <div class="flex justify-between text-emerald-700"><span>Économie</span><span>{{ number_format($pack->savingAmount() * max(1, (int) $selectedPack['quantity']), 0, ',', ' ') }} DA</span></div>
                                            </div>
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            @foreach ($pack->items as $packItem)
                                                @php
                                                    $component = collect($availability['components'] ?? [])->firstWhere('pack_item_id', $packItem->id);
                                                    $baseProduct = $packItem->product;
                                                    $isCategory = ! empty($packItem->category_id);
                                                    $storeId = $baseProduct?->store_id ?? \App\Services\StoreContext::id();
                                                    $variants = \App\Models\Product::query()
                                                        ->when($packItem->category_id, fn ($q) => $q->where('category_id', $packItem->category_id))
                                                        ->when(! $packItem->category_id && $baseProduct, fn ($q) => $q->where('category_id', $baseProduct->category_id)->where('name', 'like', $baseProduct->name.'%'))
                                                        ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
                                                        ->where('status', '!=', \App\Models\Product::STATUS_OFFLINE)
                                                        ->orderBy('name')
                                                        ->limit(20)
                                                        ->get();
                                                @endphp
                                                <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                                                    <div>
                                                        @if ($isCategory)
                                                            <p class="font-medium text-zinc-800">{{ $packItem->category?->name }} (au choix) × {{ $packItem->quantity }}</p>
                                                        @else
                                                            <p class="font-medium text-zinc-800">{{ $baseProduct?->name }} × {{ $packItem->quantity }}</p>
                                                        @endif
                                                        <p class="text-xs text-zinc-500">{{ $packItem->variant_hint ?: 'Standard' }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        @if ($packItem->selection_mode === 'manual' || $isCategory)
                                                            <select wire:change="setPackComponentProduct({{ $packIndex }}, {{ $packItem->id }}, $event.target.value)" class="rounded-lg border border-zinc-300 px-2 py-1 text-xs focus:border-brand-600 focus:outline-none">
                                                                <option value="">Auto</option>
                                                                @foreach ($variants as $variant)
                                                                    <option value="{{ $variant->id }}" @selected(($selectedPack['selected_products'][$packItem->id] ?? null) == $variant->id)>{{ $variant->name }} — {{ $variant->reference }}</option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                        @if (($component['status'] ?? 'available') === 'available')
                                                            <span class="text-xs text-emerald-600">🟢 Disponible</span>
                                                        @else
                                                            <span class="text-xs text-rose-600">🔴 Indisponible</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if ($availability && ! $availability['is_available'])
                                            <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700">❌ {{ $availability['message'] }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if (count($rows))
                        <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                                    <tr>
                                        <th class="px-3 py-2">Article</th>
                                        <th class="px-3 py-2 w-24">Qté</th>
                                        <th class="px-3 py-2">PU</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                        <th class="px-3 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach ($items as $index => $item)
                                        @php $product = $picked[$item['product_id']] ?? null; @endphp
                                        <tr>
                                            <td class="px-3 py-2">
                                                <p class="font-medium text-zinc-800">{{ $product?->name ?? 'Article #'.$item['product_id'] }}</p>
                                                <p class="text-xs text-zinc-400 font-mono">{{ $product?->reference }}</p>
                                                @if (($itemFree[$item['product_id']] ?? 0) < $item['quantity'])
                                                    <p class="mt-0.5 text-xs text-rose-600">⚠ Dépasse le stock disponible (libre {{ $itemFree[$item['product_id']] ?? 0 }})</p>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" min="1" wire:model="items.{{ $index }}.quantity" class="w-20 rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none" />
                                            </td>
                                            <td class="px-3 py-2 text-zinc-600">{{ money($item['unit_price']) }}</td>
                                            <td class="px-3 py-2 text-right font-semibold text-zinc-900">{{ money($item['quantity'] * $item['unit_price']) }}</td>
                                            <td class="px-3 py-2">
                                                <button type="button" wire:click="removeItem({{ $index }})" class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600"><flux:icon.trash variant="mini" /></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach (collect($rows)->where('is_pack_component', true) as $row)
                                        @php $product = $picked[$row['product_id']] ?? null; @endphp
                                        <tr class="bg-blue-50/30">
                                            <td class="px-3 py-2">
                                                <p class="font-medium text-zinc-800">{{ $product?->name ?? 'Article #'.$row['product_id'] }}</p>
                                                <p class="text-xs text-zinc-500">Pack: {{ $row['pack_name'] }}</p>
                                            </td>
                                            <td class="px-3 py-2">{{ $row['quantity'] }}</td>
                                            <td class="px-3 py-2 text-zinc-600">{{ money($row['unit_price']) }}</td>
                                            <td class="px-3 py-2 text-right font-semibold text-zinc-900">{{ money($row['line_total'] ?? ($row['quantity'] * $row['unit_price'])) }}</td>
                                            <td class="px-3 py-2"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-4 rounded-xl bg-zinc-50 py-6 text-center text-sm text-zinc-500">Ajoutez au moins un article.</p>
                    @endif
                    @error('items') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Dates</h2>
                    <div class="mt-3 space-y-3">
                        <div class="space-y-1">
                            <label class="text-sm text-zinc-600">Début</label>
                            <input wire:model="start_date" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            @error('start_date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm text-zinc-600">Fin</label>
                            <input wire:model="end_date" type="date" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            @error('end_date') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Montants</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500">Sous-total</dt><dd>{{ money($subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-emerald-700">Économie packs</dt><dd class="text-emerald-700">− {{ money($packSavings) }}</dd></div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-zinc-500">Remise manuelle</dt>
                            <input wire:model="discount" type="number" min="0" class="w-28 rounded-lg border border-zinc-300 px-2 py-1 text-right text-sm focus:border-brand-600 focus:outline-none" />
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-zinc-500">Caution</dt>
                            <input wire:model="caution" type="number" min="0" class="w-28 rounded-lg border border-zinc-300 px-2 py-1 text-right text-sm focus:border-brand-600 focus:outline-none" />
                        </div>
                        <div class="border-t border-zinc-100 pt-2 flex justify-between text-base font-semibold"><dt>Total</dt><dd>{{ money($total) }}</dd></div>
                    </dl>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Notes</h2>
                    <textarea wire:model="notes" rows="3" class="mt-3 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand-800 px-4 py-3 text-sm font-medium text-white hover:bg-brand-700">
                    {{ $rental?->exists ? 'Enregistrer les modifications' : 'Créer la réservation' }}
                </button>
            </div>
        </form>
    </div>
</div>
