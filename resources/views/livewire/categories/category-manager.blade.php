<div>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Catégories</h1>
            <p class="page-subtitle">Organisez vos articles par catégories et sous-catégories.</p>
            @if ($currentStoreName)
                <p class="mt-1 inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">
                    <flux:icon.building-storefront class="size-3.5" /> Boutique : {{ $currentStoreName }}
                </p>
            @endif
        </div>

        <x-flash />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-pad lg:col-span-1">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $editingId ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h2>
                <form wire:submit="save" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Nom</label>
                        <input wire:model="name" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="Costumes" />
                        @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Catégorie parente (sous-catégorie)</label>
                        <select wire:model="parent_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
                            <option value="">— Aucune —</option>
                            @foreach ($allCategories as $cat)
                                <option value="{{ $cat->id }}" @selected($cat->id === $parent_id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Couleur</label>
                        <input wire:model="color" type="color" class="h-10 w-full rounded-xl border border-zinc-300" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Tailles disponibles</label>
                        <input wire:model="sizesInput" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" placeholder="40, 42, 50, 58" />
                        <p class="text-xs text-zinc-500">Séparées par des virgules. Proposées automatiquement à la création d'un article de cette catégorie. Laissez vide pour hériter de la catégorie parente, ou utiliser la liste générale.</p>
                        @error('sizesInput') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($needsStore)
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Boutique *</label>
                            <select wire:model="store_id" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                <option value="">— Sélectionnez une boutique —</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('store_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ $editingId ? 'Enregistrer' : 'Ajouter' }}</button>
                        @if ($editingId)
                            <button type="button" wire:click="$set('editingId', null)" wire:click="reset('name','parent_id','color','sizesInput')" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Annuler</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card card-pad lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">Catégories existantes</h2>
                    @if ($needsStore)
                        <select wire:model.live="filterStoreId" class="rounded-lg border border-zinc-300 px-2 py-1 text-xs focus:border-brand-600 focus:outline-none">
                            <option value="">— Toutes les boutiques —</option>
                            @foreach ($stores as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @forelse ($categories as $cat)
                        <div class="rounded-xl border border-zinc-200 p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full" style="background: {{ $cat->color ?? '#14213f' }}"></span>
                                    <span class="font-medium text-zinc-900">{{ $cat->name }}</span>
                                    <span class="badge-zinc">{{ $cat->products_count }} art.</span>
                                </div>
                                <div class="flex gap-1">
                                    <button wire:click="edit({{ $cat->id }})" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100"><flux:icon.pencil-square variant="mini" /></button>
                                    <button wire:click="delete({{ $cat->id }})" wire:confirm="Supprimer cette catégorie ?" class="rounded-lg p-1.5 text-zinc-500 hover:bg-rose-50 hover:text-rose-600"><flux:icon.trash variant="mini" /></button>
                                </div>
                            </div>
                            @if (! empty($cat->sizes))
                                <p class="mt-1.5 text-xs text-zinc-500">Tailles : {{ implode(', ', $cat->sizes) }}</p>
                            @endif
                            @if ($cat->children->count())
                                <div class="mt-2 space-y-1 border-t border-zinc-100 pt-2">
                                    @foreach ($cat->children as $child)
                                        <div class="flex items-center justify-between pl-4 text-sm">
                                            <span class="text-zinc-600">{{ $child->name }}</span>
                                            <div class="flex gap-1">
                                                <button wire:click="edit({{ $child->id }})" class="rounded p-1 text-zinc-400 hover:text-zinc-700"><flux:icon.pencil-square variant="mini" /></button>
                                                <button wire:click="delete({{ $child->id }})" wire:confirm="Supprimer ?" class="rounded p-1 text-zinc-400 hover:text-rose-600"><flux:icon.trash variant="mini" /></button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="col-span-full py-6 text-center text-sm text-zinc-500">Aucune catégorie.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>