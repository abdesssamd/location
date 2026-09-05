<?php

namespace App\Livewire\Support;

use App\Models\SupportTicket;
use App\Services\StoreContext;
use App\Services\SupportService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Espace support cote magasin : liste des tickets, ouverture d'un nouveau
 * ticket et fil de discussion avec le support LouerPro.
 */
#[Layout('components.layouts.app')]
class SupportCenter extends Component
{
    use WithFileUploads, WithPagination;

    public ?int $ticketId = null;

    public string $subject = '';
    public string $category = 'bug';
    public string $priority = SupportTicket::PRIORITY_NORMAL;
    public string $body = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public string $reply = '';
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $replyAttachments = [];

    public bool $showForm = false;
    public string $filterStatus = '';

    public function mount(?int $ticket = null): void
    {
        $this->authorize('viewAny', SupportTicket::class);

        if ($ticket) {
            $this->openTicket($ticket);
        }
    }

    public function openTicket(int $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $this->authorize('view', $ticket);

        $this->ticketId = $ticket->id;
        $this->showForm = false;
        SupportService::markRead($ticket, asSupport: false);
    }

    public function closeTicketView(): void
    {
        $this->ticketId = null;
        $this->reply = '';
        $this->replyAttachments = [];
    }

    public function startNew(): void
    {
        $this->authorize('create', SupportTicket::class);
        $this->reset(['subject', 'body', 'attachments', 'ticketId']);
        $this->category = 'bug';
        $this->priority = SupportTicket::PRIORITY_NORMAL;
        $this->showForm = true;
    }

    public function submit(): void
    {
        $this->authorize('create', SupportTicket::class);

        $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'in:'.implode(',', array_keys(SupportTicket::categoryLabels()))],
            'priority' => ['required', 'in:'.implode(',', array_keys(SupportTicket::priorityLabels()))],
            'attachments.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $storeId = StoreContext::id();
        abort_if($storeId === null, 403, 'Aucun magasin associé à votre compte.');

        $ticket = SupportService::open(
            $storeId,
            auth()->user(),
            $this->subject,
            $this->body,
            $this->category,
            $this->priority,
            $this->storeAttachments($this->attachments, $storeId)
        );

        $this->reset(['subject', 'body', 'attachments']);
        $this->showForm = false;
        $this->ticketId = $ticket->id;

        session()->flash('status', 'Ticket '.$ticket->reference.' ouvert. Notre équipe vous répondra rapidement.');
    }

    public function sendReply(): void
    {
        $ticket = SupportTicket::findOrFail($this->ticketId);
        $this->authorize('reply', $ticket);

        $this->validate([
            'reply' => ['required', 'string', 'max:5000'],
            'replyAttachments.*' => ['nullable', 'image', 'max:5120'],
        ]);

        SupportService::reply(
            $ticket,
            auth()->user(),
            $this->reply,
            fromSupport: false,
            attachments: $this->storeAttachments($this->replyAttachments, $ticket->store_id)
        );

        $this->reset(['reply', 'replyAttachments']);
    }

    /**
     * Les pièces jointes vont sur le disque privé : une capture d'écran peut
     * montrer des données clients, elle ne doit pas être servie publiquement.
     *
     * @param array<int, mixed> $files
     * @return array<int, string>
     */
    protected function storeAttachments(array $files, int $storeId): array
    {
        $paths = [];

        foreach ($files as $file) {
            if ($file) {
                $paths[] = $file->store('support/'.$storeId, 'local');
            }
        }

        return $paths;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        $tickets = SupportTicket::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByRaw('CASE WHEN unread_for_store > 0 THEN 0 ELSE 1 END')
            ->latest('last_reply_at')
            ->paginate(10);

        $current = $this->ticketId
            ? SupportTicket::with('messages.user')->find($this->ticketId)
            : null;

        return view('livewire.support.support-center', [
            'tickets' => $tickets,
            'current' => $current,
            'statusLabels' => SupportTicket::statusLabels(),
            'priorityLabels' => SupportTicket::priorityLabels(),
            'categoryLabels' => SupportTicket::categoryLabels(),
        ]);
    }
}
