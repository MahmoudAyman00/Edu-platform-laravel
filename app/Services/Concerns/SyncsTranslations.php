<?php

namespace App\Services\Concerns;

use App\Exceptions\AppException;
use App\Models\Category;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;

/**
 * Shared helper for translatable entities.
 */
trait SyncsTranslations
{
    /**
     * @param  array<int, array{locale: string, title?: string, text?: string, description?: ?string}>  $translations
     */
    protected function syncTranslations(Category|Course|Lesson|Exam|Question|QuestionOption $model, array $translations): void
    {
        $locales = array_column($translations, 'locale');

        if (count($locales) !== count(array_unique($locales))) {
            throw AppException::fromKey('messages.common.duplicate_locale', 'DUPLICATE_LOCALE', 422);
        }

        $model->translations()->delete();
        $model->translations()->createMany($translations);
    }
}
