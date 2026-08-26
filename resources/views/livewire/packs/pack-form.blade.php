<div>
    <div class="space-y-6">
        <div>
            <a href="{{ route('packs.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux packs</a>
            <h1 class="page-title">{{ $packId ? 'Modifier le pack' : 'Nouveau pack' }}</h1>
            <p class="page-subtitle">Composez plusieurs articles avec un prix global spécial.</p>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Informations</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @if ($needsStore)
                            <div class="space-y-2 sm:col-span-2">
                                <label class="text-sm font-medium text-zinc-700">Magasin *</label>
                                <select wire:model="store_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                    <option value="">— Sélectionnez un magasin —</option>
                                    @foreach ($stores as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('store_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Nom *</label>
                            <input wire:model="name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" placeholder="Pack Mariage Élégance" />
                            @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Référence *</label>
                            <input wire:model="reference" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            @error('reference') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Catégorie</label>
                            <select wire:model="category_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                <option value="">— Sans catégorie —</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @foreach ($cat->children as $child)
                                        <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Statut</label>
                            <select wire:model="status" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                @foreach (\App\Models\Pack::statusLabels() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700">Description</label>
                            <textarea wire:model="description" rows="3" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Composition du pack</h2>
                    <p class="mt-1 text-xs text-zinc-500">Ajoutez des articles précis OU des catégories « au choix » (ex : 1 robe + n'importe quelle chaussure).</p>
                    <div class="mt-3 inline-flex rounded-xl border border-zinc-300 bg-zinc-50 p-1">
                        <button type="button" wire:click="$set('composeMode', 'product')" @class([
                            'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                            'bg-white text-zinc-900 shadow-sm' => $composeMode === 'product',
                            'text-zinc-500 hover:text-zinc-700' => $composeMode !== 'product',
                        ])>
                            Article précis
                        </button>
                        <button type="button" wire:click="$set('composeMode', 'category')" @class([
                            'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                            'bg-white text-zinc-900 shadow-sm' => $composeMode === 'category',
                            'text-zinc-500 hover:text-zinc-700' => $composeMode !== 'category',
                        ])>
                            Catégorie (au choix)
                        </button>
                    </div>

                    @if ($composeMode === 'product')
                        <div class="mt-3">
                            <div class="relative">
                                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                                <input wire:model.live.debounce.250ms="product_search" placeholder="Ajouter un article par nom ou référence..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:outline-none" />
                            </div>

                            @if ($product_search && $products->isNotEmpty())
                                <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200">
                                    @foreach ($products as $product)
                                        <button type="button" wire:click="addProduct({{ $product->id }})" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                            <div>
                                                <p class="font-medium text-zinc-800">{{ $product->name }}</p>
                                                <p class="text-xs text-zinc-400 font-mono">{{ $product->reference }} · Stock: {{ $product->quantity }}</p>
                                            </div>
                                            <span>{{ money($product->rental_price) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="mt-3 flex gap-2">
                            <select wire:model="selectedCategoryId" class="flex-1 rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                <option value="">— Choisir une catégorie —</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @foreach ($cat->children as $child)
                                        <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <button type="button" wire:click="addSelectedCategory" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Ajouter</button>
                        </div>
                        <p class="mt-2 text-xs text-zinc-400">Au moment de la location, un article disponible de cette catégorie sera proposé (le vendeur pourra en choisir un autre).</p>
                    @endif

                    @if (count($items))
                        <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                                    <tr>
                                        <th class="px-3 py-2">Article</th>
                                        <th class="px-3 py-2">Qté</th>
                                        <th class="px-3 py-2">Mode</th>
                                        <th class="px-3 py-2">Variante</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                        @foreach ($items as $index => $item)
                                            @php
                                                $product = $picked[$item['product_id']] ?? null;
                                                $isCategory = ! empty($item['category_id']) && empty($item['product_id']);
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2">
                                                    @if ($isCategory)
                                                        <p class="font-medium text-zinc-800">{{ $categoryMap[$item['category_id']]->name ?? 'Catégorie' }}</p>
                                                        <span class="badge-blue">Au choix</span>
                                                    @else
                                                        <p class="font-medium text-zinc-800">{{ $product?->name ?? 'Article #'.$item['product_id'] }}</p>
                                                        <p class="text-xs text-zinc-400 font-mono">{{ $product?->reference }}</p>
                                                    @endif
                                                </td>
                                            <td class="px-3 py-2 w-24">
                                                <input type="number" min="1" wire:model="items.{{ $index }}.quantity" class="w-20 rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none" />
                                            </td>
                                            <td class="px-3 py-2 w-36">
                                                <select wire:model="items.{{ $index }}.selection_mode" class="w-full rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none">
                                                    <option value="auto">Auto</option>
                                                    <option value="manual">Choix manuel</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input wire:model="items.{{ $index }}.variant_hint" class="w-full rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none" placeholder="Ex: Taille 52 / Pointure 42" />
                                            </td>
                                            <td class="px-3 py-2 w-10">
                                                <button type="button" wire:click="removeItem({{ $index }})" class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600"><flux:icon.trash variant="mini" /></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-4 rounded-xl bg-zinc-50 py-6 text-center text-sm text-zinc-500">Ajoutez les articles du pack.</p>
                    @endif
                    @error('items') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Photos</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-4">
                        @foreach ($existingPhotos as $photo)
                            <div class="group relative overflow-hidden rounded-xl ring-1 ring-zinc-200">
                                <img src="{{ $photo['url'] }}" class="aspect-square w-full object-cover" />
                                @if ($photo['is_primary'])
                                    <span class="absolute left-2 top-2 badge-green">Principale</span>
                                @endif
                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                                    <button type="button" wire:click="setPrimaryExisting({{ $photo['id'] }})" class="rounded-lg bg-white/90 p-1.5 text-zinc-700"><flux:icon.star class="size-4" /></button>
                                    <button type="button" wire:click="removeExistingPhoto({{ $photo['id'] }})" wire:confirm="Supprimer cette photo ?" class="rounded-lg bg-white/90 p-1.5 text-rose-600"><flux:icon.trash class="size-4" /></button>
                                </div>
                            </div>
                        @endforeach

                        @foreach ($photos as $index => $photo)
                            <div class="relative overflow-hidden rounded-xl ring-1 ring-zinc-200">
                                <img src="{{ $photo->temporaryUrl() }}" class="aspect-square w-full object-cover" />
                                <button type="button" wire:click="removeTemporaryPhoto({{ $index }})" class="absolute right-2 top-2 rounded-lg bg-white/90 p-1.5 text-rose-600"><flux:icon.trash class="size-4" /></button>
                            </div>
                        @endforeach

                        <label class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 text-zinc-400 transition hover:border-brand-600 hover:text-brand-600">
                            <flux:icon.photo class="size-7" />
                            <span class="text-xs font-medium">Ajouter</span>
                            <input type="file" wire:model="photos" multiple accept="image/*" class="hidden" />
                        </label>
                    </div>
                    @error('photos.*') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-6">
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Prix du pack</h2>
                    <div class="mt-3 space-y-3">
                        <div class="space-y-1">
                            <label class="text-sm text-zinc-600">Mode</label>
                            <select wire:model.live="pricing_mode" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                <option value="fixed">Prix fixe</option>
                                <option value="calculated">Prix calculé</option>
                            </select>
                        </div>

                        @if ($pricing_mode === \App\Models\Pack::PRICING_FIXED)
                            <div class="space-y-1">
                                <label class="text-sm text-zinc-600">Prix pack ({{ currency_symbol(store_currency()) }})</label>
                                <input wire:model="pack_price" type="number" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-1">
                                    <label class="text-sm text-zinc-600">Type remise</label>
                                    <select wire:model="discount_type" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                        <option value="percent">%</option>
                                        <option value="amount">Montant</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-sm text-zinc-600">Valeur</label>
                                    <input wire:model="discount_value" type="number" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                                </div>
                            </div>
                        @endif

                        <div class="space-y-1">
                            <label class="text-sm text-zinc-600">Caution ({{ currency_symbol(store_currency()) }})</label>
                            <input wire:model="caution" type="number" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                        </div>

                        <dl class="rounded-xl bg-zinc-50 p-3 text-sm">
                            <div class="flex justify-between"><dt class="text-zinc-500">Prix normal</dt><dd>{{ money($this->normalPrice) }}</dd></div>
                            <div class="mt-1 flex justify-between font-semibold"><dt>Prix final</dt><dd>{{ money($this->finalPrice) }}</dd></div>
                            <div class="mt-1 flex justify-between text-emerald-700"><dt>Économie</dt><dd>{{ money($this->savings) }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Conditions de location</h2>
                    <p class="mt-1 text-xs text-zinc-500">Une ligne par condition.</p>
                    <textarea wire:model="rental_conditions" rows="6" class="mt-3 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" placeholder="Ex: Nettoyage inclus&#10;Retour avant 18h"></textarea>
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand-800 px-4 py-3 text-sm font-medium text-white hover:bg-brand-700">Enregistrer le pack</button>
            </div>
        </form>
    </div>
</div>

