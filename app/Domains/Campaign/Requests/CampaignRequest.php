<?php

namespace App\Domains\Campaign\Requests;

use App\Domains\Category\Models\Category;
use App\Domains\Plan\Models\Plan;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('status', Plan::STATUS_ACTIVE)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('status', Category::STATUS_ACTIVE)],
            'media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'category_ids' => $this->input('category_ids', []),
        ]);
    }
}
