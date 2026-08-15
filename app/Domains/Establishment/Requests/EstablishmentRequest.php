<?php

namespace App\Domains\Establishment\Requests;

use App\Domains\Establishment\Models\Establishment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstablishmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retorna as regras para criar ou editar um estabelecimento.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'document' => [
                'required',
                'string',
                'regex:/^[A-Z0-9]{12}[0-9]{2}$/',
                Rule::unique('establishments', 'document')->ignore($this->route('id')),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:32'],
            'complement' => ['nullable', 'string', 'max:255'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('state_id', $this->input('state_id')),
            ],
            'neighborhood_id' => [
                'nullable',
                'integer',
                Rule::exists('neighborhoods', 'id')->where('city_id', $this->input('city_id')),
            ],
            'zip_code' => ['nullable', 'string', 'max:16'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in([
                Establishment::STATUS_ACTIVE,
                Establishment::STATUS_INACTIVE,
                Establishment::STATUS_BLOCKED,
            ])],
            'opening_hours' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => strtoupper((string) $this->onlyLettersAndNumbers($this->input('document'))),
            'phone' => $this->onlyLettersAndNumbers($this->input('phone')),
            'zip_code' => $this->onlyLettersAndNumbers($this->input('zip_code')),
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
