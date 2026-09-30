<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
