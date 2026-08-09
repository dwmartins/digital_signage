<?php

namespace App\Domains\Profile\Requests;

use App\Domains\Appearance\Models\UserAppearanceSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppearanceSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preset' => ['required', 'string', Rule::in(array_keys(UserAppearanceSetting::presetOptions()))],
            'primary' => ['required', 'string', Rule::in(array_keys(UserAppearanceSetting::primaryOptions()))],
            'surface' => ['required', 'string', Rule::in(array_keys(UserAppearanceSetting::surfaceOptions()))],
            'dark_mode' => ['required', 'boolean'],
        ];
    }
}
