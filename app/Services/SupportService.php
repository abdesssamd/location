<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Logique commune aux deux cotes du support : le magasin qui ouvre un ticket
 * et l'equipe LouerPro qui repond. Centralisee pour que les compteurs de
 * non-lus et les changements de statut restent coherents des deux cotes.
 */
class SupportService
{
    /**
     * @param array<int, string> $attachments
     */
    public static function open(int $storeId, User $author, string $subject, string $body, string $category, string $priority, array $attachments = []): SupportTicket
    {
        return DB::transaction(function () use ($storeId, $author, $subject, $body, $category, $priority, $attachments) {
            $ticket = SupportTicket::create([
                'store_id' => $storeId,
                'user_id' => $author->id,
                'reference' => ReferenceGenerator::reference('SUP', SupportTicket::class),
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'status' => SupportTicket::STATUS_OPEN,
                'last_reply_at' => now(),
                'last_reply_by' => SupportMessage::AUTHOR_STORE,
                // Le premier message attend une reponse du support.
                'unread_for_admin' => 1,
                'unread_for_store' => 0,
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'author_type' => SupportMessage::AUTHOR_STORE,
                'author_name' => $author->name,
                'body' => $body,
                'attachment_paths' => $attachments ?: null,
            ]);

            return $ticket;
        });
    }

    /**
     * @param array<int, string> $attachments
     */
    public static function reply(SupportTicket $ticket, User $author, string $body, bool $fromSupport, array $attachments = []): SupportMessage
    {
        return DB::transaction(function () use ($ticket, $author, $body, $fromSupport, $attachments) {
            $message = SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'author_type' => $fromSupport ? SupportMessage::AUTHOR_SUPPORT : SupportMessage::AUTHOR_STORE,
                'author_name' => $author->name,
                'body' => $body,
                'attachment_paths' => $attachments ?: null,
            ]);

            $updates = [
                'last_reply_at' => now(),
                'last_reply_by' => $message->author_type,
            ];

            // Le non-lu s'incremente pour le destinataire, pas pour l'auteur.
            if ($fromSupport) {
                $updates['unread_for_store'] = $ticket->unread_for_store + 1;
                $updates['unread_for_admin'] = 0;
            } else {
                $updates['unread_for_admin'] = $ticket->unread_for_admin + 1;
                $updates['unread_for_store'] = 0;
            }

            // Repondre a un ticket resolu le rouvre : le probleme persiste.
            if (! $ticket->isOpen()) {
                $updates['status'] = SupportTicket::STATUS_OPEN;
                $updates['resolved_at'] = null;
                $updates['closed_at'] = null;
            } elseif ($fromSupport) {
                $updates['status'] = SupportTicket::STATUS_PENDING;
            }

            $ticket->update($updates);

            return $message;
        });
    }

    public static function changeStatus(SupportTicket $ticket, string $status): void
    {
        $ticket->update([
            'status' => $status,
            'resolved_at' => $status === SupportTicket::STATUS_RESOLVED ? now() : null,
            'closed_at' => $status === SupportTicket::STATUS_CLOSED ? now() : null,
        ]);
    }

    /** Remet a zero le compteur du cote qui vient de lire le fil. */
    public static function markRead(SupportTicket $ticket, bool $asSupport): void
    {
        $ticket->update($asSupport ? ['unread_for_admin' => 0] : ['unread_for_store' => 0]);
    }

    /** Nombre de tickets avec des messages non lus, pour la pastille du menu. */
    public static function unreadCountForStore(?int $storeId): int
    {
        if (! $storeId) {
            return 0;
        }

        return SupportTicket::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('unread_for_store', '>', 0)
            ->count();
    }

    public static function unreadCountForAdmin(): int
    {
        return SupportTicket::withoutGlobalScopes()
            ->where('unread_for_admin', '>', 0)
            ->count();
    }
}
