<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            @php $hasPackComponents = $rental->items->contains(fn($it) => (bool) $it->is_pack_component); @endphp
            <div>
                <a href="{{ route('rentals.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900" wire:navigate>← Retour aux locations</a>
                <h1 class="page-title">{{ $rental->reference }}</h1>
                <p class="page-subtitle">Créée le {{ $rental->created_at?->format('d/m/Y H:i') }} par {{ $rental->user?->name ?? '—' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($hasPackComponents)
                    <a href="{{ route('contracts.pack-return.show', $rental) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50"><flux:icon.document-text variant="mini" /> Fiche retour pack</a>
                    <a href="{{ route('contracts.pack-return.pdf', $rental) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50"><flux:icon.arrow-down-tray variant="mini" /> PDF retour pack</a>
                @endif
                @if ($rental->status === 'reserved')
                    <button wire:click="checkout" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"><flux:icon.play variant="mini" /> Démarrer la location</button>
                    <button wire:click="cancel" wire:confirm="Annuler cette réservation ?" class="inline-flex items-center gap-2 rounded-xl border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50"><flux:icon.x-mark variant="mini" /> Annuler</button>
                @endif
                @if (in_array($rental->status, ['reserved', 'active']))
                    <button wire:click="complete" wire:confirm="Confirmer le retour et terminer la location ?" class="inline-flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"><flux:icon.arrow-down-tray variant="mini" /> Retour / Terminer</button>
                @endif
                @if (in_array($rental->status, ['reserved', 'active']) && auth()->user()->can('rentals.create'))
                    <a href="{{ route('rentals.edit', $rental) }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate><flux:icon.pencil-square variant="mini" /> Modifier</a>
                @endif
            </div>
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif
        @if (session('error'))
            <x-flash :status="session('error')" type="error" />
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">Statut</h2>
                    <span class="{{ \App\Models\Rental::statusBadge($rental->status) }}">{{ $statusLabels[$rental->status] ?? $rental->status }}</span>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Du</dt><dd class="font-medium">{{ $rental->start_date?->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Au</dt><dd class="font-medium">{{ $rental->end_date?->format('d/m/Y') }}</dd></div>
                    @if ($rental->actual_return_date)
                        <div class="flex justify-between"><dt class="text-zinc-500">Retour effectif</dt><dd class="font-medium">{{ $rental->actual_return_date?->format('d/m/Y') }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-zinc-500">Durée</dt><dd class="font-medium">{{ $rental->days }} jour(s)</dd></div>
                </dl>
            </div>

            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Client</h2>
                <div class="mt-3 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-800">{{ strtoupper(substr($rental->customer->first_name, 0, 1)) }}{{ strtoupper(substr($rental->customer->last_name, 0, 1)) }}</span>
                    <div>
                        <a href="{{ route('customers.show', $rental->customer) }}" wire:navigate class="font-medium text-zinc-900 hover:text-brand-700">{{ $rental->customer->full_name }}</a>
                        <p class="text-xs text-zinc-500">{{ $rental->customer->phone }}</p>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Montants</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Sous-total</dt><dd>{{ money($rental->subtotal) }}</dd></div>
                    @if ($rental->pack_savings > 0)
                        <div class="flex justify-between"><dt class="text-emerald-700">Économie packs</dt><dd class="text-emerald-700">− {{ money($rental->pack_savings) }}</dd></div>
                    @endif
                    @if ($rental->discount > 0)
                        <div class="flex justify-between"><dt class="text-zinc-500">Remise</dt><dd class="text-rose-600">− {{ money($rental->discount) }}</dd></div>
                    @endif
                    @if ($rental->late_fee > 0)
                        <div class="flex justify-between"><dt class="text-rose-600">Pénalité retard</dt><dd class="text-rose-600">{{ money($rental->late_fee) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-zinc-500">Caution</dt><dd>{{ money($rental->caution) }}</dd></div>
                    <div class="border-t border-zinc-100 pt-2 flex justify-between font-semibold"><dt>Total</dt><dd>{{ money($rental->total) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-emerald-600">Payé</dt><dd class="text-emerald-600">{{ money($rental->paid_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Reste</dt><dd class="font-semibold {{ $remaining > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ money($remaining) }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-pad lg:col-span-2">
                <h2 class="text-sm font-semibold text-zinc-900">Articles loués</h2>
                @php
                    $packGroups = $rental->items->where('is_pack_component', true)->groupBy(fn ($item) => $item->pack_id ?: $item->pack_name);
                    $standaloneItems = $rental->items->where('is_pack_component', false);
                @endphp

                @if ($packGroups->isNotEmpty())
                    <div class="mt-3 space-y-3">
                        @foreach ($packGroups as $packKey => $group)
                            <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $group->first()->pack_name ?? 'Pack' }}</p>
                                <p class="text-xs text-zinc-500">Prix pack enregistré au niveau location. Composition ci-dessous.</p>
                                <div class="mt-2 divide-y divide-blue-100">
                                    @foreach ($group as $item)
                                        <div class="flex items-center justify-between py-2 text-sm">
                                            <div>
                                                <p class="font-medium text-zinc-800">{{ $item->product?->name }}</p>
                                                <p class="text-xs font-mono text-zinc-400">{{ $item->product?->reference }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold">× {{ $item->quantity }}</p>
                                                <p class="text-xs text-zinc-500">{{ money($item->line_total) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($standaloneItems->isNotEmpty())
                <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-3 py-2">Article</th>
                                <th class="px-3 py-2">Qté</th>
                                <th class="px-3 py-2">PU</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($standaloneItems as $item)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('products.show', $item->product) }}" wire:navigate class="font-medium text-zinc-800 hover:text-brand-700">{{ $item->product?->name }}</a>
                                        <span class="block font-mono text-xs text-zinc-400">{{ $item->product?->reference }}</span>
                                    </td>
                                    <td class="px-3 py-2">{{ $item->quantity }}</td>
                                    <td class="px-3 py-2 text-zinc-600">{{ money($item->unit_price) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold">{{ money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @if ($rental->notes)
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Notes</h3>
                        <p class="mt-1 text-sm text-zinc-600">{{ $rental->notes }}</p>
                    </div>
                @endif

                @if (in_array($rental->status, ['reserved', 'active']))
                    <div class="mt-6 rounded-xl border border-zinc-200 p-4">
                        <h3 class="text-sm font-semibold text-zinc-900">Retour détaillé</h3>
                        <p class="mt-1 text-xs text-zinc-500">Renseignez l'état de chaque article avant de terminer la location.</p>
                        <div class="mt-3 space-y-3">
                            @foreach ($rental->items as $index => $item)
                                <div class="rounded-xl border border-zinc-200 p-3">
                                    <div class="flex items-center justify-between">
                                        <p class="font-medium text-zinc-800">{{ $item->product?->name }} <span class="text-zinc-500">× {{ $item->quantity }}</span></p>
                                        @if ($item->is_pack_component)
                                            <span class="text-xs text-blue-600">{{ $item->pack_name }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 grid gap-2 md:grid-cols-3">
                                        <select wire:model="returnItems.{{ $index }}.condition" class="rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none">
                                            <option value="good">Bon</option>
                                            <option value="damaged">Endommagé</option>
                                            <option value="cleaning">Nettoyage</option>
                                            <option value="lost">Perdu</option>
                                        </select>
                                        <input type="number" min="0" wire:model="returnItems.{{ $index }}.damage_fee" placeholder="Dommage DA" class="rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none" />
                                        <input wire:model="returnItems.{{ $index }}.notes" placeholder="Note retour" class="rounded-lg border border-zinc-300 px-2 py-1 text-sm focus:border-brand-600 focus:outline-none" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label class="text-sm font-medium text-zinc-700">Photos du retour</label>
                            <input type="file" multiple wire:model="returnPhotos" class="mt-1 block w-full text-sm text-zinc-600" />
                        </div>
                    </div>
                @endif
            </div>

            @php
                $returnPhotos = $rental->items->pluck('return_image_paths')->filter()->flatten()->filter()->values();
            @endphp
            @if ($returnPhotos->isNotEmpty())
                <div class="card card-pad">
                    <h2 class="text-sm font-semibold text-zinc-900">Photos du retour</h2>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($returnPhotos as $path)
                            <a href="{{ asset('storage/'.$path) }}" target="_blank" rel="noopener">
                                <img src="{{ asset('storage/'.$path) }}" class="h-24 w-24 rounded-lg border border-zinc-200 object-cover" alt="Retour" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Encaissement</h2>
                @if ($remaining > 0 && in_array($rental->status, ['reserved', 'active']))
                    <form wire:submit="recordPayment" class="mt-3 space-y-3">
                        <div class="space-y-1">
                            <label class="text-sm text-zinc-600">Montant à encaisser</label>
                            <input wire:model="paid_amount" type="number" min="1" max="{{ $remaining }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            @error('paid_amount') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm text-zinc-600">Mode de paiement</label>
                            <select wire:model="payment_method" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                <option value="cash">Espèces</option>
                                <option value="card">Carte</option>
                                <option value="transfer">Virement</option>
                                <option value="check">Chèque</option>
                            </select>
                        </div>
                        @if ($payment_method !== 'card')
                            <div class="space-y-1">
                                <label class="text-sm text-zinc-600">Preuve de paiement (photo)</label>
                                <input type="file" wire:model="paymentProof" multiple accept="image/*" class="mt-1 block w-full text-sm text-zinc-600" />
                                @error('paymentProof.*') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Encaisser {{ money(min($this->paid_amount ?: 0, $remaining)) }}</button>
                    </form>
                @elseif ($remaining === 0)
                    <p class="mt-3 rounded-xl bg-emerald-50 py-4 text-center text-sm font-medium text-emerald-700">Location entièrement payée.</p>
                @else
                    <p class="mt-3 rounded-xl bg-zinc-50 py-4 text-center text-sm text-zinc-500">Encaissement fermé pour cette location.</p>
                @endif
            </div>
        </div>
    </div>
</div>
