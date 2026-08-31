<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">{{ $productId ? 'Modifier l\'article' : 'Nouvel article' }}</h1>
            <p class="page-subtitle">{{ $productId ? $product->name : 'Créez un article avec ses photos, prix et stock.' }}</p>
        </div>

        {{-- Erreurs globales : limite de plan, magasin manquant, échec d'autorisation --}}
        <x-flash />

        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="card card-pad">
                        <h2 class="text-sm font-semibold text-zinc-900">Informations</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @if ($needsStore)
                                <div class="space-y-2 sm:col-span-2">
                                    <label class="text-sm font-medium text-zinc-700">Magasin *</label>
                                    <select wire:model="store_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                                        <option value="">— Sélectionnez un magasin —</option>
                                        @foreach ($stores as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('store_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Nom de l'article *</label>
                                <input wire:model="name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Costume 3 pièces" />
                                @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Référence *</label>
                                <input wire:model="reference" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                                @error('reference') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Catégorie</label>
                                <div class="flex gap-2">
                                    <select wire:model.live="category_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                                        <option value="">— Sans catégorie —</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @foreach ($cat->children as $child)
                                                <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="$toggle('showQuickCategory')" class="inline-flex shrink-0 items-center gap-1 rounded-xl border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" title="Nouvelle catégorie">
                                        <flux:icon.plus variant="mini" /> Catégorie
                                    </button>
                                </div>

                                @if ($showQuickCategory)
                                    <div class="mt-2 flex items-center gap-2 rounded-xl bg-zinc-50 p-3">
                                        <input wire:model="newCategoryName" placeholder="Nom de la catégorie" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                                        <input type="color" wire:model="newCategoryColor" class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-zinc-300 bg-white p-1" title="Couleur" />
                                        <button type="button" wire:click="quickAddCategory" class="shrink-0 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Ajouter</button>
                                    </div>
                                    @error('newCategoryName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                @endif

                                @error('category_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Marque</label>
                                <input wire:model="brand" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Tailles</label>
                                @if ($usingCategorySizes)
                                    <p class="text-xs text-zinc-500">Tailles de la catégorie sélectionnée.</p>
                                @endif
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($sizePresets as $presetSize)
                                        <label class="inline-flex cursor-pointer items-center">
                                            <input type="checkbox" value="{{ $presetSize }}" wire:model="sizes" class="peer sr-only" />
                                            <span class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 peer-checked:border-brand-800 peer-checked:bg-brand-800 peer-checked:text-white">{{ $presetSize }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('sizes') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                <input wire:model="size" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Ou une taille personnalisée (ex : 52 / M)" />
                                <p class="text-xs text-zinc-500">Cochez plusieurs tailles pour créer automatiquement un article par taille (référence suffixée, ex : {{ strtoupper($reference ?? 'ART-000001') }}-M). Les photos seront attachées à la première taille.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Couleur</label>
                                <input wire:model="color" list="color-presets" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Ex : Rouge, Noir..." />
                                <div class="flex flex-wrap gap-1.5 pt-0.5">
                                    @foreach ($colorPresets as $presetName => $presetHex)
                                        <button type="button" wire:click="$set('color', '{{ $presetName }}')" title="{{ $presetName }}" class="h-5 w-5 rounded-full border border-zinc-300 transition hover:scale-110" style="background-color: {{ $presetHex }}"></button>
                                    @endforeach
                                </div>
                                <datalist id="color-presets">
                                    @foreach ($colorPresets as $presetName => $presetHex)
                                        <option value="{{ $presetName }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Matière</label>
                                <input wire:model="material" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Code-barres</label>
                                <input wire:model="barcode" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Automatique si vide" />
                            </div>
                            <div class="space-y-2 sm:col-span-2">
                                <label class="text-sm font-medium text-zinc-700">Description</label>
                                <textarea wire:model="description" rows="3" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none"></textarea>
                            </div>
                            <div class="space-y-2 sm:col-span-2">
                                <label class="text-sm font-medium text-zinc-700">Notes internes</label>
                                <textarea wire:model="notes" rows="2" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card card-pad">
                        <h2 class="text-sm font-semibold text-zinc-900">Photos</h2>
                        <p class="mt-1 text-xs text-zinc-500">Format JPG/PNG/WebP. La première photo devient la photo principale.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            @foreach ($existingPhotos as $photo)
                                <div class="group relative overflow-hidden rounded-xl ring-1 ring-zinc-200">
                                    <img src="{{ $photo['url'] }}" class="aspect-square w-full object-cover" />
                                    @if ($photo['is_primary'])
                                        <span class="absolute left-2 top-2 badge-green">Principale</span>
                                    @endif
                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                                        <button type="button" wire:click="setPrimaryExisting({{ $photo['id'] }})" title="Définir principale" class="rounded-lg bg-white/90 p-1.5 text-zinc-700 hover:bg-white">
                                            <flux:icon.star class="size-4" />
                                        </button>
                                        <button type="button" wire:click="removeExistingPhoto({{ $photo['id'] }})" wire:confirm="Supprimer cette photo ?" title="Supprimer" class="rounded-lg bg-white/90 p-1.5 text-rose-600 hover:bg-white">
                                            <flux:icon.trash class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($photos as $index => $photo)
                                <div class="relative overflow-hidden rounded-xl ring-1 ring-zinc-200">
                                    <img src="{{ $photo->temporaryUrl() }}" class="aspect-square w-full object-cover" />
                                    <button type="button" wire:click="removeTemporaryPhoto({{ $index }})" class="absolute right-2 top-2 rounded-lg bg-white/90 p-1.5 text-rose-600">
                                        <flux:icon.trash class="size-4" />
                                    </button>
                                </div>
                            @endforeach

                            <label class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 text-zinc-400 transition hover:border-brand-600 hover:text-brand-600">
                                <flux:icon.photo class="size-7" />
                                <span class="text-xs font-medium">Ajouter des photos</span>
                                <input type="file" wire:model="photos" multiple accept="image/*" class="hidden" />
                            </label>
                        </div>
                        @error('photos.*') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card card-pad">
                        <h2 class="text-sm font-semibold text-zinc-900">Prix</h2>
                        <div class="mt-4 space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Prix de location ({{ currency_symbol(store_currency()) }}) *</label>
                                <input wire:model="rental_price" type="number" step="0.01" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                                @error('rental_price') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Caution ({{ currency_symbol(store_currency()) }})</label>
                                <input wire:model="caution_price" type="number" step="0.01" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Prix de vente (DA, optionnel)</label>
                                <input wire:model="sale_price" type="number" step="0.01" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                        </div>
                    </div>

                    <div class="card card-pad">
                        <h2 class="text-sm font-semibold text-zinc-900">Stock</h2>
                        <div class="mt-4 space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Quantité</label>
                                <input wire:model="quantity" type="number" min="0" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                                @error('quantity') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700">Statut</label>
                                <select wire:model="status" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700">
                            {{ $productId ? 'Enregistrer' : 'Créer l\'article' }}
                        </button>
                        <a href="{{ route('products.index') }}" class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>Annuler</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>