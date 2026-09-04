<div class="relative">
    @php
        // La cloche vit sur deux fonds : la barre latérale sombre et le header
        // mobile clair. Les couleurs suivent donc l'emplacement.
        $onDark = $variant === 'dark';
    @endphp
    <button type="button" wire:click="$toggle('open')" @class([
        'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
        'text-zinc-400 hover:bg-white/10 hover:text-white' => $onDark,
        'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800' => ! $onDark,
    ])>
        <flux:icon.bell class="size-5" />
        @if ($count > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold text-white">{{ $count > 9 ? '9+' : $count }}</span>
        @endif
    </button>

    @if ($open)
        {{-- Le panneau s'ouvre du côté où il y a de la place : vers la droite
             depuis la barre latérale, vers la gauche depuis le header mobile. --}}
        <div @class([
            'absolute top-12 z-50 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg',
            'left-0' => $onDark,
            'right-0' => ! $onDark,
        ]) @click.away="open = false" wire:click.away="$set('open', false)">
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                <p class="text-sm font-semibold text-zinc-900">Notifications</p>
                @if ($count > 0)
                    <button wire:click="markAllRead" class="text-xs text-brand-700 hover:underline">Tout marquer lu</button>
                @endif
            </div>
            <div class="max-h-80 overflow-y-auto">
                @forelse ($unread as $notification)
                    @php $data = $notification->data; @endphp
                    <a href="{{ url($data['url'] ?? '#') }}" wire:click="markAsRead('{{ $notification->id }}')" class="flex gap-3 border-b border-zinc-50 px-4 py-3 hover:bg-zinc-50">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-800"></span>
                        <div>
                            @if (($data['type'] ?? '') === 'low_stock')
                                <p class="text-sm text-zinc-800">Stock bas : <span class="font-medium">{{ $data['product_name'] }}</span></p>
                                <p class="mt-0.5 text-xs text-zinc-500">Il reste {{ $data['quantity'] }} exemplaire(s).</p>
                            @elseif (($data['type'] ?? '') === 'upcoming_return')
                                <p class="text-sm text-zinc-800">Retour prévu : <span class="font-medium">{{ $data['customer_name'] }}</span></p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $data['rental_reference'] }} — le {{ \Carbon\Carbon::parse($data['end_date'])->format('d/m/Y') }}</p>
                            @else
                                <p class="text-sm text-zinc-800">{{ $notification->type }}</p>
                            @endif
                            <p class="mt-1 text-[10px] text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <p class="py-10 text-center text-sm text-zinc-500">Aucune notification.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>