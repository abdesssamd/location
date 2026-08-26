<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Packs</h1>
                <p class="page-subtitle">{{ $packs->total() }} pack(s) enregistrés.</p>
            </div>
            @can('packs.create')
                <a href="{{ route('packs.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700" wire:navigate>
                    <flux:icon.plus variant="mini" /> Nouveau pack
                </a>
            @endcan
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        <div class="card p-4">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="relative md:col-span-2">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Nom ou référence..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                </div>
                <select wire:model.live="status" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($packs as $pack)
                @php
                    $normal = $pack->normalPrice();
                    $final = $pack->finalPrice();
                    $saving = max(0, $normal - $final);
                    $img = $pack->primaryImage();
                @endphp
                <div class="card overflow-hidden">
                    <div class="aspect-[16/9] bg-zinc-100">
                        @if ($img)
                            <img src="{{ Storage::disk('public')->url($img->path) }}" alt="{{ $pack->name }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center text-zinc-400">
                                <flux:icon.photo class="size-8" />
                            </div>
                        @endif
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono text-xs text-zinc-400">{{ $pack->reference }}</p>
                                <a href="{{ route('packs.show', $pack) }}" wire:navigate class="font-semibold text-zinc-900 hover:text-brand-700">{{ $pack->name }}</a>
                            </div>
                            <span class="{{ \App\Models\Pack::statusBadge($pack->status) }}">{{ $statuses[$pack->status] ?? $pack->status }}</span>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3 text-sm">
                            <div class="flex justify-between"><span class="text-zinc-500">Prix normal</span><span>{{ money($normal) }}</span></div>
                            <div class="flex justify-between font-semibold"><span class="text-zinc-700">Prix pack</span><span>{{ money($final) }}</span></div>
                            <div class="mt-1 flex justify-between text-emerald-700"><span>Économie</span><span>{{ money($saving) }}</span></div>
                        </div>

                        <div class="text-xs text-zinc-500">{{ $pack->items->sum('quantity') }} article(s) • Caution {{ money($pack->caution) }}</div>

                        <div class="flex gap-1">
                            <a href="{{ route('packs.show', $pack) }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Voir</a>
                            @can('packs.edit')
                                <a href="{{ route('packs.edit', $pack) }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Modifier</a>
                            @endcan
                            @can('packs.create')
                                <button type="button" wire:click="duplicate({{ $pack->id }})" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Dupliquer</button>
                            @endcan
                            @can('packs.archive')
                                @if ($pack->status !== \App\Models\Pack::STATUS_ARCHIVED)
                                    <button type="button" wire:click="archive({{ $pack->id }})" wire:confirm="Archiver ce pack ?" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Archiver</button>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="card card-pad md:col-span-2 xl:col-span-3">
                    <p class="text-center text-sm text-zinc-500">Aucun pack trouvé.</p>
                </div>
            @endforelse
        </div>

        <div>
            {{ $packs->links() }}
        </div>
    </div>
</div>

