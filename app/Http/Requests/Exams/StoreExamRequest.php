<?php

namespace App\Http\Requests\Exams;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'pass_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_final' => ['sometimes', 'boolean'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
