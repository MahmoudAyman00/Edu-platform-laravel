<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(['admin', 'student'])],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
            'password' => ['sometimes', 'string', 'min:8'],
        ];
    }
}
