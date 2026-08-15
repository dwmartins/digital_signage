<?php

namespace App\Domains\Plan\Requests;

use App\Domains\Plan\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'screen_limit' => ['required', 'integer', 'min:1', 'max:10000'],
            'media_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'billing_cycle' => ['required', Rule::in([Plan::BILLING_MONTHLY, Plan::BILLING_YEARLY])],
            'media_type' => ['required', Rule::in([Plan::MEDIA_IMAGE, Plan::MEDIA_VIDEO])],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'status' => ['required', Rule::in([Plan::STATUS_ACTIVE, Plan::STATUS_INACTIVE])],
        ];
    }
}
