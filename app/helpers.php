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
