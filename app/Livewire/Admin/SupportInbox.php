<?php

namespace App\Livewire\Admin;

use App\Models\SupportTicket;
use App\Services\SupportService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Boite de reception du support, cote equipe LouerPro : tous les tickets de
 * tous les magasins, avec reponse et changement de statut.
 */
#[Layout('components.layouts.admin')]
class SupportInbox extends Component
{
    use WithFileUploads, WithPagination;

    public ?int $ticketId = null;

    public string $reply = '';
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $replyAttachments = [];

    public string $filterStatus = '';
    public string $filterPriority = '';
    public string $search = '';

    public function mount(?int $ticket = null): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        if ($ticket) {
            $this->openTicket($ticket);
        }
    }

    public function openTicket(int $ticketId): void
    {
        // withoutGlobalScopes() : le support n'appartient a aucun magasin, le
        // scope tenant masquerait tous les tickets.
        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($ticketId);

        $this->ticketId = $ticket->id;
        SupportService::markRead($ticket, asSupport: true);
    }

    public function closeTicketView(): void
    {
        $this->ticketId = null;
        $this->reply = '';
        $this->replyAttachments = [];
    }

    public function sendReply(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($this->ticketId);

        $this->validate([
            'reply' => ['required', 'string', 'max:5000'],
            'replyAttachments.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $paths = [];
        foreach ($this->replyAttachments as $file) {
            if ($file) {
                $paths[] = $file->store('support/'.$ticket->store_id, 'local');
            }
        }

        SupportService::reply($ticket, auth()->user(), $this->reply, fromSupport: true, attachments: $paths);

        $this->reset(['reply', 'replyAttachments']);
    }

    public function changeStatus(string $status): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        if (! array_key_exists($status, SupportTicket::statusLabels())) {
            return;
        }

        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($this->ticketId);
        SupportService::changeStatus($ticket, $status);

        session()->flash('status', 'Ticket marqué « '.SupportTicket::statusLabels()[$status].' ».');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $tickets = SupportTicket::withoutGlobalScopes()
            ->with('store')
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPriority, fn ($q) => $q->where('priority', $this->filterPriority))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('subject', 'like', '%'.$this->search.'%')
                    ->orWhere('reference', 'like', '%'.$this->search.'%')
                    ->orWhereHas('store', fn ($s) => $s->where('name', 'like', '%'.$this->search.'%'));
            }))
            // Les tickets en attente de reponse remontent en tete.
            ->orderByRaw('CASE WHEN unread_for_admin > 0 THEN 0 ELSE 1 END')
            ->latest('last_reply_at')
            ->paginate(12);

        $current = $this->ticketId
            ? SupportTicket::withoutGlobalScopes()->with(['messages.user', 'store', 'user'])->find($this->ticketId)
            : null;

        return view('livewire.admin.support-inbox', [
            'tickets' => $tickets,
            'current' => $current,
            'statusLabels' => SupportTicket::statusLabels(),
            'priorityLabels' => SupportTicket::priorityLabels(),
            'categoryLabels' => SupportTicket::categoryLabels(),
            'openCount' => SupportTicket::withoutGlobalScopes()->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING])->count(),
            'unreadCount' => SupportService::unreadCountForAdmin(),
        ]);
    }
}
