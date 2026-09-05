<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    public const AUTHOR_STORE = 'store';
    public const AUTHOR_SUPPORT = 'support';

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'author_type',
        'author_name',
        'body',
        'attachment_paths',
    ];

    protected function casts(): array
    {
        return [
            'attachment_paths' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function isFromSupport(): bool
    {
        return $this->author_type === self::AUTHOR_SUPPORT;
    }
}
