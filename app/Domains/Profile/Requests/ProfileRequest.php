<?php

namespace App\Domains\Profile\Requests;

use App\Domains\User\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name'        => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'       => ['required', 'string', 'max:32'],
            'birth_date'  => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'description' => ['nullable', 'string', 'max:500']
        ];
    }

    /**
     * Normaliza documento e telefone antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => $this->onlyLettersAndNumbers($this->input('document')),
            'professional_document' => $this->onlyLettersAndNumbers($this->input('professional_document')),
            'phone' => $this->onlyLettersAndNumbers($this->input('phone')),
        ]);
    }

    private function onlyLettersAndNumbers(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_replace('/[^a-zA-Z0-9]/', '', $value) ?: null;
    }
}
