<?php

namespace App\Domains\Locality\Requests;

use App\Domains\Locality\Models\State;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $id = $this->route('id');
        $table = match ($type) {
            'states' => 'states',
            'cities' => 'cities',
            'neighborhoods' => 'neighborhoods',
            default => 'states',
        };
        $nameRule = Rule::unique($table, 'name')->ignore($id);

        if ($type === 'cities') {
            $nameRule->where('state_id', $this->integer('state_id'));
        }

        if ($type === 'neighborhoods') {
            $nameRule->where('city_id', $this->integer('city_id'));
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $nameRule,
            ],
            'code' => [
                Rule::requiredIf($type === 'states'),
                'nullable',
                'string',
                'size:2',
                Rule::unique('states', 'code')->ignore($id),
            ],
            'state_id' => [
                Rule::requiredIf($type === 'cities'),
                'nullable',
                'integer',
                Rule::exists('states', 'id'),
            ],
            'city_id' => [
                Rule::requiredIf($type === 'neighborhoods'),
                'nullable',
                'integer',
                Rule::exists('cities', 'id'),
            ],
            'status' => ['required', Rule::in([
                State::STATUS_ACTIVE,
                State::STATUS_INACTIVE,
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $this->filled('code')
                ? strtoupper(trim((string) $this->input('code')))
                : null,
        ]);
    }

}
