<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'in:ar,en'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'current_password' => ['required_with:password', 'current_password'],
        ];
    }
}
