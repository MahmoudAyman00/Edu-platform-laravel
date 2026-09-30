<?php

namespace App\Http\Requests\Exams;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.text' => ['required', 'string'],
            'options' => ['sometimes', 'array', 'min:2'],
            'options.*.is_correct' => ['required', 'boolean'],
            'options.*.translations' => ['required', 'array', 'min:1'],
            'options.*.translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'options.*.translations.*.text' => ['required', 'string'],
        ];
    }
}
