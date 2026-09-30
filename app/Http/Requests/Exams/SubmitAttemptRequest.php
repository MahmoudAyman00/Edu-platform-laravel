<?php

namespace App\Http\Requests\Exams;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'min:1'],
            'answers.*.selected_option_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
