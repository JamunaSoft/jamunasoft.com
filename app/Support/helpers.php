<?php

use App\Support\Settings;

if (! function_exists('settings')) {
    /**
     * Get a website setting value (cached), with an optional default.
     */
    function settings(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('default_nameservers')) {
    /**
     * Default nameservers for newly registered domains: the
     * "domain_default_nameservers" setting (one per line), falling back to
     * the company's cluster nameservers.
     *
     * @return array<int, string>
     */
    function default_nameservers(): array
    {
        $configured = collect(explode("\n", (string) settings('domain_default_nameservers', '')))
            ->map(fn (string $host) => strtolower(trim($host)))
            ->filter()
            ->values();

        return $configured->count() >= 2
            ? $configured->all()
            : ['cl1.jamunasoft.com', 'cl2.jamunasoft.com'];
    }
}

if (! function_exists('settings_t')) {
    /**
     * Get a translatable setting: when the active locale is not the fallback,
     * a `<key>_<locale>` setting (e.g. hero_heading_bn) wins when filled.
     */
    function settings_t(string $key, mixed $default = null): mixed
    {
        $locale = app()->getLocale();

        if ($locale !== config('app.fallback_locale')) {
            $localized = Settings::get("{$key}_{$locale}");

            if (filled($localized)) {
                return $localized;
            }
        }

        return Settings::get($key, $default);
    }
}
