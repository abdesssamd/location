<x-layouts.admin title="Magasins">
    <div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Magasins</h1>
            <p class="page-subtitle">Gérer les magasins de la plateforme.</p>
        </div>
        <a href="{{ route('admin.stores.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
            <flux:icon.plus variant="mini" /> Nouveau magasin
        </a>
    </div>

    @if (session('status'))
        <x-flash :status="session('status')" />
    @endif

    <div class="card overflow-hidden">
        <div class="divide-y divide-zinc-100">
            @forelse ($stores as $store)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $store->color ? 'text-white' : 'bg-brand-50 text-brand-800' }}" @if ($store->color) style="background-color: {{ $store->color }}" @endif>
                            <flux:icon.building-storefront variant="solid" />
                        </span>
                        <div>
                            <a href="{{ route('admin.stores.show', $store) }}" class="font-medium text-zinc-900 hover:text-brand-700" wire:navigate>
                                {{ $store->name }}
                            </a>
                            <p class="text-xs text-zinc-500">
                                {{ $store->wilaya ?? '—' }} · {{ $store->phone ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden text-xs text-zinc-400 sm:block">{{ $store->users_count }} employé(s)</span>
                        @if ($store->status === 'active')
                            <span class="badge-green">Actif</span>
                        @elseif ($store->status === 'pending')
                            <span class="badge-yellow">En attente</span>
                        @else
                            <span class="badge-red">Suspendu</span>
                        @endif
                        <div class="flex gap-1">
                            @if ($store->status === 'pending')
                                <form method="POST" action="{{ route('admin.stores.approve', $store) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-emerald-500">Accepter</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.stores.show', $store) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900" wire:navigate title="Voir la boutique">
                                <flux:icon.eye variant="mini" />
                            </a>
                            <a href="{{ route('admin.stores.edit', $store) }}" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900" wire:navigate>
                                <flux:icon.pencil-square variant="mini" />
                            </a>
                            <form method="POST" action="{{ route('admin.stores.toggle-status', $store) }}">
                                @csrf
                                <button type="submit" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900">
                                    <flux:icon.power variant="mini" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-zinc-500">Aucun magasin pour le moment.</div>
            @endforelse
        </div>
    </div>

    <div>
        {{ $stores->links() }}
    </div>
</div>
</x-layouts.admin>