<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Support</h1>
            <p class="page-subtitle">{{ $openCount }} ticket(s) en cours · {{ $unreadCount }} en attente de réponse.</p>
        </div>
    </div>

    @if (session('status'))
        <x-flash :status="session('status')" />
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Liste --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="space-y-3 border-b border-zinc-100 p-4">
                <div class="relative">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-2.5 size-4 text-zinc-400" />
                    <input wire:model.live.debounce.300ms="search" placeholder="Sujet, référence, magasin..." class="w-full rounded-xl border border-zinc-300 py-2 pl-9 pr-3 text-sm focus:border-brand-600 focus:outline-none" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <select wire:model.live="filterStatus" class="rounded-xl border border-zinc-300 px-2 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Tous statuts</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="filterPriority" class="rounded-xl border border-zinc-300 px-2 py-2 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Toutes priorités</option>
                        @foreach ($priorityLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="max-h-[32rem] divide-y divide-zinc-100 overflow-y-auto">
                @forelse ($tickets as $ticket)
                    <button type="button" wire:click="openTicket({{ $ticket->id }})"
                        class="w-full p-4 text-left hover:bg-zinc-50/60 {{ $current?->id === $ticket->id ? 'bg-brand-50/40' : '' }}">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="font-mono text-[11px] text-zinc-500">{{ $ticket->reference }}</span>
                            <span class="{{ \App\Models\SupportTicket::statusBadge($ticket->status) }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                            <span class="{{ \App\Models\SupportTicket::priorityBadge($ticket->priority) }}">{{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</span>
                            @if ($ticket->unread_for_admin > 0)
                                <span class="badge-red">{{ $ticket->unread_for_admin }}</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm font-medium text-zinc-900">{{ $ticket->subject }}</p>
                        <p class="mt-0.5 flex items-center justify-between text-xs text-zinc-500">
                            <span class="truncate">{{ $ticket->store?->name ?? 'Magasin supprimé' }}</span>
                            <span class="shrink-0 text-zinc-400">{{ $ticket->last_reply_at?->diffForHumans() }}</span>
                        </p>
                    </button>
                @empty
                    <p class="p-10 text-center text-sm text-zinc-500">Aucun ticket.</p>
                @endforelse
            </div>

            @if ($tickets->hasPages())
                <div class="border-t border-zinc-100 p-3">{{ $tickets->links() }}</div>
            @endif
        </div>

        {{-- Discussion --}}
        <div class="lg:col-span-3">
            @if ($current)
                <div class="card overflow-hidden">
                    <div class="border-b border-zinc-100 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-zinc-500">{{ $current->reference }}</span>
                                    <span class="{{ \App\Models\SupportTicket::statusBadge($current->status) }}">{{ $statusLabels[$current->status] ?? $current->status }}</span>
                                    <span class="{{ \App\Models\SupportTicket::priorityBadge($current->priority) }}">{{ $priorityLabels[$current->priority] ?? $current->priority }}</span>
                                </div>
                                <h2 class="mt-1 text-base font-semibold text-zinc-900">{{ $current->subject }}</h2>
                                <p class="text-xs text-zinc-500">
                                    {{ $current->store?->name }} · {{ $current->user?->name }} ·
                                    {{ $categoryLabels[$current->category] ?? $current->category }} ·
                                    {{ $current->created_at?->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <button type="button" wire:click="closeTicketView" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100">
                                <flux:icon.x-mark variant="mini" />
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($statusLabels as $value => $label)
                                @if ($current->status !== $value)
                                    <button type="button" wire:click="changeStatus('{{ $value }}')"
                                        class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">
                                        Marquer « {{ $label }} »
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="max-h-[26rem] space-y-4 overflow-y-auto p-4">
                        @foreach ($current->messages as $message)
                            <div class="flex {{ $message->isFromSupport() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[80%] rounded-2xl px-4 py-3 {{ $message->isFromSupport() ? 'bg-brand-800 text-white' : 'bg-zinc-100 text-zinc-800' }}">
                                    <div class="flex items-center gap-2 text-xs {{ $message->isFromSupport() ? 'text-white/70' : 'text-zinc-500' }}">
                                        <span class="font-medium">{{ $message->isFromSupport() ? 'Support · '.$message->author_name : $message->author_name }}</span>
                                        <span>·</span>
                                        <span>{{ $message->created_at?->format('d/m H:i') }}</span>
                                    </div>
                                    <p class="mt-1 whitespace-pre-line text-sm">{{ $message->body }}</p>

                                    @if ($message->attachment_paths)
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($message->attachment_paths as $index => $path)
                                                @php($url = route('files.support', [$message, $index]))
                                                <a href="{{ $url }}" target="_blank">
                                                    <img src="{{ $url }}" class="size-20 rounded-lg border {{ $message->isFromSupport() ? 'border-white/30' : 'border-zinc-300' }} object-cover" alt="Pièce jointe" />
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-zinc-100 p-4">
                        <form wire:submit="sendReply" class="space-y-3">
                            <textarea wire:model="reply" rows="3" placeholder="Votre réponse au magasin..." class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                            @error('reply') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                            <div class="flex flex-wrap items-center gap-3">
                                <input type="file" wire:model="replyAttachments" multiple accept="image/*" class="text-sm text-zinc-600" />
                                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-brand-800 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="sendReply">Répondre</span>
                                    <span wire:loading wire:target="sendReply">Envoi…</span>
                                </button>
                            </div>
                            @error('replyAttachments.*') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </form>
                    </div>
                </div>
            @else
                <div class="card flex h-full min-h-[20rem] flex-col items-center justify-center p-10 text-center">
                    <flux:icon.chat-bubble-left-right class="size-10 text-zinc-300" />
                    <p class="mt-3 text-sm text-zinc-500">Sélectionnez un ticket pour afficher la discussion.</p>
                </div>
            @endif
        </div>
    </div>
</div>
