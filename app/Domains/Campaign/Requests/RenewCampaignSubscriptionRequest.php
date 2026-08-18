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
            'payment_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'expected_ends_at' => ['nullable', 'date'],
        ];
    }
}
