<?php

namespace App\Domains\Category\Requests;

use App\Domains\Category\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retorna as regras para criar ou editar uma categoria.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($this->route('id')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([
                Category::STATUS_ACTIVE,
                Category::STATUS_INACTIVE,
            ])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
        ]);
    }
}
