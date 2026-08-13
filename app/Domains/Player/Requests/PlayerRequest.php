<?php

namespace App\Domains\Player\Requests;

use App\Domains\Player\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('players', 'code')->ignore($this->route('id'))],
            'hostname' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'operating_system' => ['nullable', 'string', 'max:255'],
            'architecture' => ['nullable', Rule::in(['x86_64', 'arm64', 'armv7'])],
            'memory_mb' => ['nullable', 'integer', 'min:1', 'max:1048576'],
            'storage_mb' => ['nullable', 'integer', 'min:1', 'max:1073741824'],
            'status' => ['required', Rule::in([Player::STATUS_ACTIVE, Player::STATUS_MAINTENANCE, Player::STATUS_BLOCKED, Player::STATUS_STOCK])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'code' => strtoupper(trim((string) $this->input('code')))]);
    }
}
