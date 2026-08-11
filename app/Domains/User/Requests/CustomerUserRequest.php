<?php

namespace App\Domains\User\Requests;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('id');
        $passwordRules = $userId
            ? ['nullable', 'string', 'min:8', 'max:255']
            : ['required', 'string', 'min:8', 'max:255'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['required', 'string', 'max:32'],
            'status' => ['required', 'string', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE,
                User::STATUS_BLOCKED,
            ])],
            'password' => $passwordRules,
            'audit_logs_enabled' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
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
