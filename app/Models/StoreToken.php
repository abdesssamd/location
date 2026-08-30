<?php

namespace App\Models;

use App\Services\ReferenceGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Le token n'est jamais conservé en clair : la base ne garde que son empreinte
 * SHA-256 et un aperçu masqué. La valeur complète n'existe qu'au moment de la
 * génération, dans la propriété transitoire $plainText.
 */
class StoreToken extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /** Valeur en clair, disponible uniquement lors de la génération. */
    public ?string $plainText = null;

    protected $fillable = [
        'store_id',
        'token',
        'token_hash',
        'status',
        'revoked_at',
        'created_by',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function hashFor(string $plainText): string
    {
        return hash('sha256', trim($plainText));
    }

    /** Aperçu affichable : de quoi reconnaître le token sans pouvoir s'en servir. */
    public static function mask(string $plainText): string
    {
        $plainText = trim($plainText);

        return substr($plainText, 0, 8).'••••'.substr($plainText, -4);
    }

    /** Émet un nouveau token pour un magasin ; la valeur en clair n'est renvoyée qu'ici. */
    public static function issue(int $storeId, ?int $userId = null): self
    {
        $plainText = ReferenceGenerator::storeToken();

        $token = self::create([
            'store_id' => $storeId,
            'token' => self::mask($plainText),
            'token_hash' => self::hashFor($plainText),
            'status' => self::STATUS_ACTIVE,
            'created_by' => $userId,
        ]);

        $token->plainText = $plainText;

        return $token;
    }

    /** Retrouve un token actif à partir de la valeur présentée par le client. */
    public static function findActiveByPlainText(string $plainText): ?self
    {
        return self::where('token_hash', self::hashFor($plainText))
            ->where('status', self::STATUS_ACTIVE)
            ->with('store')
            ->first();
    }
}
