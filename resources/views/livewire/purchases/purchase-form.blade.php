<div>
    <div class="space-y-6">
        <div>
            <a href="{{ route('purchases.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux achats</a>
            <h1 class="page-title">Nouvel achat</h1>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Fournisseur (facultatif) --}}
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Fournisseur <span class="font-normal text-zinc-400">(facultatif)</span></h2>
                    <div class="mt-3">
                        @if ($selectedSupplier)
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2">
                                <div>
                                    <p class="font-medium text-zinc-900">{{ $selectedSupplier->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $selectedSupplier->phone }}</p>
                                </div>
                                <button type="button" wire:click="$set('supplier_id', null)" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100"><flux:icon.x-mark variant="mini" /></button>
                            </div>
                        @else
                            <div class="relative">
                                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                                <input wire:model.live.debounce.250ms="supplier_search" placeholder="Rechercher un fournisseur..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                            </div>
                            <button type="button" wire:click="$toggle('showNewSupplier')" class="mt-2 inline-flex items-center gap-1 rounded-lg border border-brand-300 px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50">
                                <flux:icon.plus variant="mini" /> Nouveau fournisseur
                            </button>

                            @if ($supplier_search && $suppliers->isNotEmpty())
                                <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200">
                                    @foreach ($suppliers as $supplier)
                                        <button type="button" wire:click="selectSupplier({{ $supplier->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                            <span class="text-zinc-700">{{ $supplier->name }}</span>
                                            <span class="text-xs text-zinc-400">{{ $supplier->phone }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @if ($showNewSupplier)
                                <div class="mt-3 space-y-2 rounded-xl bg-zinc-50 p-3">
                                    <input wire:model="new_supplier_name" placeholder="Nom du fournisseur" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                                    <input wire:model="new_supplier_phone" placeholder="Téléphone" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                                    @error('new_supplier_name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <button type="button" wire:click="createSupplier" class="rounded-lg bg-brand-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700">Créer et sélectionner</button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Articles reçus --}}
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Articles reçus</h2>
                    <div class="relative mt-3">
                        <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                        <input wire:model.live.debounce.250ms="product_search" placeholder="Rechercher un article par nom, référence ou code-barres..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                    </div>

                    @if ($product_search && $products->isNotEmpty())
                        <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200">
                            @foreach ($products as $product)
                                <button type="button" wire:click="addProduct({{ $product->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50">
                                    <span>
                                        <span class="block text-zinc-700">{{ $product->name }}</span>
                                        <span class="block text-xs text-zinc-400">{{ $product->reference }} · Stock actuel : {{ $product->quantity }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 space-y-2">
                        @forelse ($items as $index => $item)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 p-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900">{{ $item['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $item['reference'] }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-zinc-500">Qté</label>
                                    <input type="number" min="1" value="{{ $item['quantity'] }}"
                                        wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                        class="w-16 rounded-lg border border-zinc-300 px-2 py-1 text-center text-sm focus:border-brand-600 focus:outline-none" />
                                </div>
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-zinc-500">Coût unit.</label>
                                    <input type="number" min="0" value="{{ $item['unit_cost'] }}"
                                        wire:change="updateUnitCost({{ $index }}, $event.target.value)"
                                        class="w-24 rounded-lg border border-zinc-300 px-2 py-1 text-center text-sm focus:border-brand-600 focus:outline-none" />
                                </div>
                                <p class="w-24 text-right text-sm font-semibold text-zinc-900">{{ money($item['quantity'] * $item['unit_cost']) }}</p>
                                <button type="button" wire:click="removeItem({{ $index }})" class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600"><flux:icon.trash variant="mini" /></button>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-400">Aucun article ajouté. Recherchez un article ci-dessus.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-700">Notes</label>
                    <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                </div>
            </div>

            {{-- Récapitulatif & paiement --}}
            <div class="space-y-4">
                <div class="card card-pad space-y-3">
                    <h2 class="text-sm font-semibold text-zinc-900">Paiement</h2>

                    <div class="flex items-center justify-between border-t border-zinc-100 pt-3 text-base">
                        <span class="font-semibold text-zinc-900">Total</span>
                        <span class="font-semibold tabular-nums text-zinc-900">{{ money($this->total) }}</span>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-zinc-600">Méthode de paiement</label>
                        <select wire:model="payment_method" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                            <option value="cash">Espèces</option>
                            <option value="card">Carte</option>
                            <option value="transfer">Virement</option>
                            <option value="check">Chèque</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-zinc-600">Montant payé au fournisseur</label>
                        <input type="number" min="0" wire:model="paid_amount" placeholder="0" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm focus:border-brand-600 focus:outline-none" />
                        <p class="mt-1 text-xs text-zinc-500">Laissez à 0 si l'achat n'est pas encore réglé.</p>
                    </div>

                    <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="w-full rounded-xl bg-brand-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Réceptionner l'achat</span>
                        <span wire:loading wire:target="save">Enregistrement…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
