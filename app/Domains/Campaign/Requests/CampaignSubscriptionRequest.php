<?php

namespace App\Domains\Campaign\Requests;

use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in([
                CampaignSubscription::STATUS_PENDING,
                CampaignSubscription::STATUS_ACTIVE,
                CampaignSubscription::STATUS_EXPIRED,
                CampaignSubscription::STATUS_CANCELLED,
            ])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }
}
