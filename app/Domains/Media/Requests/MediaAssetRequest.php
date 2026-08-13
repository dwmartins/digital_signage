<?php

namespace App\Domains\Media\Requests;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fileRules = [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'max:102400', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov'];

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'file' => $fileRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }
}
