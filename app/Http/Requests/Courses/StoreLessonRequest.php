<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_path' => ['nullable', 'string', 'max:1024'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
