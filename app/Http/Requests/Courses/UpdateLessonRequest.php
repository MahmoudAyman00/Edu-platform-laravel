<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_path' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'duration' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
