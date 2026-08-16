<?php

namespace App\Domains\DisplayPoint\Requests;

use App\Domains\DisplayPoint\Models\DisplayPoint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisplayPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'establishment_id' => ['required', 'integer', 'exists:establishments,id'],
            'screen_id' => ['nullable', 'integer', 'exists:screens,id', Rule::unique('display_points', 'screen_id')->ignore($this->route('id'))],
            'player_id' => ['nullable', 'integer', 'exists:players,id', Rule::unique('display_points', 'player_id')->ignore($this->route('id'))],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'orientation' => ['required', Rule::in([
                DisplayPoint::ORIENTATION_LANDSCAPE,
                DisplayPoint::ORIENTATION_PORTRAIT,
            ])],
            'status' => ['required', Rule::in([DisplayPoint::STATUS_ACTIVE, DisplayPoint::STATUS_MAINTENANCE, DisplayPoint::STATUS_INACTIVE])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
