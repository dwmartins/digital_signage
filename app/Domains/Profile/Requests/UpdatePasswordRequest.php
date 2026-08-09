<?php

namespace App\Domains\Profile\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password'  => ['required', 'string', 'current_password'],
            'new_password'      => ['required', 'string', 'min:8', 'confirmed'],
            'force_logout'      => ['boolean'],
        ];
    }
}