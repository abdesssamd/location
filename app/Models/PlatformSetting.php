<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Réglages généraux de la plateforme (hors magasin) : inscription publique,
 * durée de la période de démonstration, mode d'acceptation des demandes.
 */
class PlatformSetting extends Model
{
    public const CACHE_KEY = 'platform.settings';

    public const MODE_AUTO = 'auto';
    public const MODE_MANUAL = 'manual';

    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'signup_enabled' => true,
        'signup_mode' => self::MODE_MANUAL,
        'trial_days' => 14,
        'trial_plan_id' => null,
    ];

    /** @return array<string, mixed> */
    public static function all_settings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = self::query()->pluck('value', 'key')->all();

            $values = self::DEFAULTS;

            foreach ($stored as $key => $raw) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = json_decode((string) $raw, true);
                }
            }

            return $values;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all_settings()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);

        Cache::forget(self::CACHE_KEY);
    }

    public static function signupEnabled(): bool
    {
        return (bool) self::get('signup_enabled');
    }

    /** Les inscriptions sont-elles activées immédiatement, ou validées à la main ? */
    public static function autoApproves(): bool
    {
        return self::get('signup_mode') === self::MODE_AUTO;
    }

    public static function trialDays(): int
    {
        return max(0, (int) self::get('trial_days'));
    }

    public static function trialPlan(): ?Plan
    {
        $planId = self::get('trial_plan_id');

        if ($planId) {
            $plan = Plan::where('is_active', true)->find($planId);

            if ($plan) {
                return $plan;
            }
        }

        return Plan::where('is_active', true)->orderBy('sort_order')->first();
    }
}
