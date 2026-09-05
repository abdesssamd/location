<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use BelongsToStore, HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'store_id',
        'user_id',
        'reference',
        'subject',
        'category',
        'priority',
        'status',
        'last_reply_at',
        'last_reply_by',
        'unread_for_store',
        'unread_for_admin',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    /**
     * withoutGlobalScopes() : le ticket parent fixe deja le magasin. Sans ce
     * bypass, le support (qui n'appartient a aucun magasin) ne verrait aucun
     * message.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->withoutGlobalScopes()->oldest();
    }

    /** Un ticket resolu ou ferme n'accepte plus de reponse. */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_PENDING], true);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_PENDING => 'En cours',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_CLOSED => 'Fermé',
        ];
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'badge-blue',
            self::STATUS_PENDING => 'badge-orange',
            self::STATUS_RESOLVED => 'badge-green',
            self::STATUS_CLOSED => 'badge-zinc',
            default => 'badge-zinc',
        };
    }

    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_NORMAL => 'Normale',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }

    public static function priorityBadge(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW => 'badge-zinc',
            self::PRIORITY_NORMAL => 'badge-blue',
            self::PRIORITY_HIGH => 'badge-orange',
            self::PRIORITY_URGENT => 'badge-red',
            default => 'badge-zinc',
        };
    }

    public static function categoryLabels(): array
    {
        return [
            'bug' => 'Problème technique',
            'question' => 'Question d\'utilisation',
            'billing' => 'Abonnement / facturation',
            'feature' => 'Suggestion d\'amélioration',
            'other' => 'Autre',
        ];
    }
}
