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

    public static function reference(string $prefix, string $model, string $column = 'reference', ?string $year = null): string
    {
        $year ??= now()->format('Y');
        $prefix = strtoupper($prefix);
        $next = (int) $model::max($column === 'number' ? $column : 'id') + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $next);
    }

    public static function nextNumber(string $model, string $column = 'id'): int
    {
        return ((int) $model::max($column)) + 1;
    }
}