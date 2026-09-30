<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'method' => ['required', 'string', Rule::in(['FAWRY_CODE', 'FAWRY_CARD'])],
            'mobile' => ['required', 'string', 'max:20'],
        ];
    }
}
