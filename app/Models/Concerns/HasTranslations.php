<?php

namespace App\Models\Concerns;

/**
 * Lightweight per-locale field overrides stored in a `translations` JSON column,
 * shaped as: {"bn": {"name": "...", "excerpt": "..."}}.
 * The base columns always hold the default-locale (English) content.
 */
trait HasTranslations
{
    public function initializeHasTranslations(): void
    {
        $this->mergeCasts(['translations' => 'array']);
        $this->mergeFillable(['translations']);
    }

    /**
     * Get a field's value in the given (or current) locale, falling back to the base column.
     */
    public function t(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        if ($locale !== config('app.fallback_locale')) {
            $value = data_get($this->translations, "{$locale}.{$field}");

            if (filled($value)) {
                return $value;
            }
        }

        return $this->{$field};
    }
}
