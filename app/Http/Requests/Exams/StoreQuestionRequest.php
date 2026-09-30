<?php

namespace App\Http\Requests\Exams;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'min:1'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'translations.*.text' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.is_correct' => ['required', 'boolean'],
            'options.*.translations' => ['required', 'array', 'min:1'],
            'options.*.translations.*.locale' => ['required', 'string', 'in:ar,en'],
            'options.*.translations.*.text' => ['required', 'string'],
        ];
    }
}
