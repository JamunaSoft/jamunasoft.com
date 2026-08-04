<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Settings
{
    protected const CACHE_KEY = 'website_settings';

    /** @var array<string, mixed>|null */
    protected static ?array $resolved = null;

    /**
     * Get a setting value by key, with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::all();

        $value = $all[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        if (static::$resolved !== null) {
            return static::$resolved;
        }

        return static::$resolved = Cache::rememberForever(static::CACHE_KEY, function (): array {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Setting::query()->pluck('value', 'key')
                ->map(fn ($value) => static::decode($value))
                ->all();
        });
    }

    /**
     * Persist a group of settings and refresh the cache.
     *
     * @param  array<string, mixed>  $values
     */
    public static function set(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => static::encode($value)],
            );
        }

        static::flush();
    }

    public static function flush(): void
    {
        static::$resolved = null;
        Cache::forget(static::CACHE_KEY);
    }

    protected static function encode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : json_encode($value);
    }

    protected static function decode(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && ! is_scalar($decoded) ? $decoded : $value;
    }
}
