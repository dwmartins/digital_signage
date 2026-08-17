<?php

namespace App\Domains\Campaign\Requests;

use App\Domains\Billing\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RenewCampaignSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', Rule::in(Transaction::paymentMethods())],
            'expected_ends_at' => ['nullable', 'date'],
        ];
    }
}
