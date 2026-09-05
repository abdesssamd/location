<div>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Support technique</h1>
                <p class="page-subtitle">Une question, un problème ? Notre équipe vous répond ici.</p>
            </div>
            @can('create', \App\Models\SupportTicket::class)
                <button type="button" wire:click="startNew" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
                    <flux:icon.plus variant="mini" /> Nouveau ticket
                </button>
            @endcan
        </div>

        @if (session('status'))
            <x-flash :status="session('status')" />
        @endif

        {{-- Formulaire de nouveau ticket --}}
        @if ($showForm)
            <div class="card card-pad">
                <h2 class="text-sm font-semibold text-zinc-900">Décrivez votre problème</h2>
                <form wire:submit="submit" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700">Sujet</label>
                            <input wire:model="subject" placeholder="Ex : impossible d'imprimer un contrat" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none" />
                            @error('subject') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Type de demande</label>
                            <select wire:model="category" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                @foreach ($categoryLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700">Priorité</label>
                            <select wire:model="priority" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                                @foreach ($priorityLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Description</label>
                        <textarea wire:model="body" rows="5" placeholder="Expliquez ce qui se passe, et ce que vous attendiez..." class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                        @error('body') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700">Captures d'écran <span class="font-normal text-zinc-400">(facultatif)</span></label>
                        <input type="file" wire:model="attachments" multiple accept="image/*" class="block w-full text-sm text-zinc-600" />
                        <p class="text-xs text-zinc-500">Une image vaut mieux qu'un long texte : joignez une capture du problème.</p>
                        @error('attachments.*') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="attachments" class="text-xs text-zinc-500">Chargement des images…</div>

                        @if ($attachments)
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($attachments as $file)
                                    @if ($file)
                                        <img src="{{ $file->temporaryUrl() }}" class="size-16 rounded-lg border border-zinc-200 object-cover" alt="Aperçu" />
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-brand-800 px-6 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="submit">Envoyer le ticket</span>
                            <span wire:loading wire:target="submit">Envoi…</span>
                        </button>
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-zinc-300 px-6 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Annuler</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Fil de discussion --}}
        @if ($current)
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 p-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-zinc-500">{{ $current->reference }}</span>
                            <span class="{{ \App\Models\SupportTicket::statusBadge($current->status) }}">{{ $statusLabels[$current->status] ?? $current->status }}</span>
                            <span class="{{ \App\Models\SupportTicket::priorityBadge($current->priority) }}">{{ $priorityLabels[$current->priority] ?? $current->priority }}</span>
                        </div>
                        <h2 class="mt-1 text-base font-semibold text-zinc-900">{{ $current->subject }}</h2>
                        <p class="text-xs text-zinc-500">{{ $categoryLabels[$current->category] ?? $current->category }} · ouvert le {{ $current->created_at?->format('d/m/Y à H:i') }}</p>
                    </div>
                    <button type="button" wire:click="closeTicketView" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100" title="Fermer">
                        <flux:icon.x-mark variant="mini" />
                    </button>
                </div>

                <div class="max-h-[28rem] space-y-4 overflow-y-auto p-4">
                    @foreach ($current->messages as $message)
                        <div class="flex {{ $message->isFromSupport() ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[80%] rounded-2xl px-4 py-3 {{ $message->isFromSupport() ? 'bg-zinc-100 text-zinc-800' : 'bg-brand-800 text-white' }}">
                                <div class="flex items-center gap-2 text-xs {{ $message->isFromSupport() ? 'text-zinc-500' : 'text-white/70' }}">
                                    <span class="font-medium">{{ $message->isFromSupport() ? 'Support LouerPro' : $message->author_name }}</span>
                                    <span>·</span>
                                    <span>{{ $message->created_at?->format('d/m H:i') }}</span>
                                </div>
                                <p class="mt-1 whitespace-pre-line text-sm">{{ $message->body }}</p>

                                @if ($message->attachment_paths)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($message->attachment_paths as $index => $path)
                                            @php($url = route('files.support', [$message, $index]))
                                            <a href="{{ $url }}" target="_blank">
                                                <img src="{{ $url }}" class="size-20 rounded-lg border {{ $message->isFromSupport() ? 'border-zinc-300' : 'border-white/30' }} object-cover" alt="Pièce jointe" />
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($current->isOpen())
                    <div class="border-t border-zinc-100 p-4">
                        <form wire:submit="sendReply" class="space-y-3">
                            <textarea wire:model="reply" rows="3" placeholder="Votre réponse..." class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
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
                @else
                    <div class="border-t border-zinc-100 bg-zinc-50 p-4 text-center text-sm text-zinc-500">
                        Ce ticket est {{ strtolower($statusLabels[$current->status] ?? $current->status) }}.
                        Répondez ci-dessous pour le rouvrir si le problème persiste.
                        <form wire:submit="sendReply" class="mt-3 space-y-3 text-left">
                            <textarea wire:model="reply" rows="2" placeholder="Le problème est revenu..." class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                            <button type="submit" class="rounded-xl border border-zinc-300 bg-white px-5 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Rouvrir le ticket</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        {{-- Liste des tickets --}}
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 p-4">
                <h2 class="text-sm font-semibold text-zinc-900">Mes tickets</h2>
                <select wire:model.live="filterStatus" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="divide-y divide-zinc-100">
                @forelse ($tickets as $ticket)
                    <button type="button" wire:click="openTicket({{ $ticket->id }})"
                        class="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left hover:bg-zinc-50/60 {{ $current?->id === $ticket->id ? 'bg-brand-50/40' : '' }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs text-zinc-500">{{ $ticket->reference }}</span>
                                <span class="{{ \App\Models\SupportTicket::statusBadge($ticket->status) }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                                @if ($ticket->unread_for_store > 0)
                                    <span class="badge-red">{{ $ticket->unread_for_store }} nouveau(x)</span>
                                @endif
                            </div>
                            <p class="mt-1 truncate text-sm font-medium text-zinc-900">{{ $ticket->subject }}</p>
                        </div>
                        <span class="text-xs text-zinc-400">{{ $ticket->last_reply_at?->diffForHumans() }}</span>
                    </button>
                @empty
                    <div class="p-10 text-center">
                        <p class="text-sm text-zinc-500">Aucun ticket pour le moment.</p>
                        <p class="mt-1 text-xs text-zinc-400">Un problème avec l'application ? Ouvrez un ticket, nous vous répondrons.</p>
                    </div>
                @endforelse
            </div>

            @if ($tickets->hasPages())
                <div class="border-t border-zinc-100 p-4">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</div>
