<?php

namespace App\Http\Requests\Exams;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'pass_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['sometimes', 'integer', 'min:1'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
