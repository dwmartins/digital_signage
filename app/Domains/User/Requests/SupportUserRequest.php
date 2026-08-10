<?php

namespace App\Domains\User\Requests;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupportUserRequest extends FormRequest
{
    /**
     * A autorização específica fica nos middlewares de permissão da rota.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras para criar ou atualizar usuários suporte.
     *
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
        ];
    }

    /**
     * Normaliza documento e telefone antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
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
