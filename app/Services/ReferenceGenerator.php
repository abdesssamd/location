<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReferenceGenerator
{
    public static function storeToken(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $group = fn (): string => substr(str_shuffle(str_repeat($alphabet, 4)), 0, 4);

        return 'STR-'.$group().'-'.$group().'-'.$group();
    }

    /**
     * La référence est unique sur toute la base, pas par magasin : le calcul
     * du prochain numéro doit donc ignorer le scope tenant. Sinon deux magasins
     * qui créent le même jour tombent sur le même numéro, et l'insertion viole
     * la contrainte d'unicité.
     */
    public static function reference(string $prefix, string $model, string $column = 'reference', ?string $year = null): string
    {
        $year ??= now()->format('Y');
        $prefix = strtoupper($prefix);
        $numberColumn = $column === 'number' ? $column : 'id';

        $next = (int) $model::query()->withoutGlobalScopes()->max($numberColumn) + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $next);
    }

    public static function nextNumber(string $model, string $column = 'id'): int
    {
        return ((int) $model::max($column)) + 1;
    }
}