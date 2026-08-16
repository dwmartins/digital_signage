<?php

namespace App\Domains\Screen\Requests;

use App\Domains\Screen\Models\Screen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retorna as regras para criar ou editar uma tela.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('screens', 'code')->ignore($this->route('id')),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'screen_size' => ['nullable', 'numeric', 'min:1', 'max:999.9'],
            'resolution_width' => ['required', 'integer', 'min:240', 'max:16384'],
            'resolution_height' => ['required', 'integer', 'min:240', 'max:16384'],
            'status' => ['required', Rule::in([
                Screen::STATUS_ACTIVE,
                Screen::STATUS_MAINTENANCE,
                Screen::STATUS_BLOCKED,
                Screen::STATUS_STOCK,
            ])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => strtoupper(trim((string) $this->input('code'))),
            'brand' => $this->filled('brand') ? trim((string) $this->input('brand')) : null,
            'model' => $this->filled('model') ? trim((string) $this->input('model')) : null,
        ]);
    }
}
