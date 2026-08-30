<?php

use App\Models\Store;
use App\Services\StoreContext;

if (! function_exists('currency_symbol')) {
    function currency_symbol(?string $code = null): string
    {
        $code = strtoupper((string) ($code ?: 'DA'));

        return match ($code) {
            'DA' => 'DA',
            'DZD' => 'DA',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'TND' => 'DT',
            'MAD' => 'DH',
            'CAD' => 'CA$',
            'AED' => 'AED',
            'SAR' => 'SR',
            'XOF' => 'FCFA',
            default => $code,
        };
    }
}

if (! function_exists('store_currency')) {
    function store_currency(): string
    {
        $storeId = StoreContext::id();

        if ($storeId) {
            return Store::find($storeId)?->currency ?? 'DA';
        }

        return 'DA';
    }
}

if (! function_exists('money')) {
    /**
     * Formate un montant selon la devise de la boutique courante.
     */
    function money(int|float|string|null $amount, ?string $currency = null): string
    {
        $currency ??= store_currency();
        $value = (int) round((float) ($amount ?? 0));

        return currency_symbol($currency).' '.number_format($value, 0, ',', ' ');
    }
}

if (! function_exists('missing_store_message')) {
    /**
     * Message affiché quand aucun magasin n'est en contexte.
     *
     * « Sélectionnez un magasin » n'a de sens que pour le super admin, qui en gère
     * plusieurs. Pour un utilisateur de magasin, l'absence de contexte signifie que
     * son compte n'est rattaché à aucun magasin : c'est un problème de compte.
     */
    function missing_store_message(?string $action = null): string
    {
        if ((bool) optional(auth()->user())->is_super_admin) {
            return $action
                ? 'Sélectionnez le magasin concerné avant de '.$action.'.'
                : 'Sélectionnez le magasin sur lequel vous travaillez.';
        }

        return 'Votre compte n\'est rattaché à aucun magasin. Contactez votre administrateur.';
    }
}
