<?php

namespace App\Domains\Setting\Requests;

use App\Domains\Setting\Models\EmailSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', Rule::in([
                EmailSetting::ENCRYPTION_TLS,
                EmailSetting::ENCRYPTION_SSL,
            ])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'from_address' => ['required', 'email:rfc', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'host' => trim((string) $this->input('host')),
            'username' => $this->filled('username') ? trim((string) $this->input('username')) : null,
            'from_address' => strtolower(trim((string) $this->input('from_address'))),
            'from_name' => trim((string) $this->input('from_name')),
        ]);
    }
}
