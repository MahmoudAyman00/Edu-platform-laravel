<?php

namespace App\Models\Concerns;

/**
 * Best-match translation lookup: requested locale -> app locale
 * -> fallback locale -> first available.
 *
 * The using model must define a `translations()` HasMany relation
 * whose items carry a `locale` attribute.
 */
trait HasTranslations
{
    public function translation(?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', (string) config('app.fallback_locale', 'ar'))
            ?? $translations->first();
    }
}
