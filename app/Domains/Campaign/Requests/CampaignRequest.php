<?php

namespace App\Domains\Campaign\Requests;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Category\Models\Category;
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
        $creating = $this->isMethod('post');

        return [
            'subscription_id' => ['required', 'integer', 'exists:campaign_subscriptions,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in([
                Campaign::STATUS_ACTIVE,
                Campaign::STATUS_INACTIVE,
            ])],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('status', Category::STATUS_ACTIVE)],
            'display_point_ids' => ['nullable', 'array'],
            'display_point_ids.*' => ['integer', 'distinct', 'exists:display_points,id'],
            'media_asset_id' => [$creating ? 'required_without:file' : 'nullable', 'integer', 'exists:media_assets,id'],
            'file' => [$creating ? 'required_without:media_asset_id' : 'nullable', 'file', 'max:102400', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'category_ids' => $this->input('category_ids', []),
            'display_point_ids' => $this->input('display_point_ids', []),
        ]);
    }
}
