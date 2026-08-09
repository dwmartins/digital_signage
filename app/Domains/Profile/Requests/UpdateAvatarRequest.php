<?php

namespace App\Domains\Profile\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'A foto é obrigatória.',
            'avatar.image'    => 'O arquivo deve ser uma imagem.',
            'avatar.mimes'    => 'A foto deve ser nos formatos: jpg, jpeg, png ou webp.',
            'avatar.max'      => 'A foto não pode ultrapassar 2MB.',
        ];
    }
}