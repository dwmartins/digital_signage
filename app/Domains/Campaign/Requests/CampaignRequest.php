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
        return [
            'subscription_id' => ['required', 'integer', 'exists:campaign_subscriptions,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'playback_mode' => ['nullable', Rule::in([
                Campaign::PLAYBACK_SEQUENTIAL,
                Campaign::PLAYBACK_RANDOM,
            ])],
            'status' => ['nullable', Rule::in([
                Campaign::STATUS_ACTIVE,
                Campaign::STATUS_PENDING,
                Campaign::STATUS_PAUSED,
                Campaign::STATUS_CANCELLED,
            ])],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('status', Category::STATUS_ACTIVE)],
            'display_point_ids' => ['nullable', 'array'],
            'display_point_ids.*' => ['integer', 'distinct', 'exists:display_points,id'],
            'media_asset_ids' => ['nullable', 'array'],
            'media_asset_ids.*' => ['integer', 'distinct', 'exists:media_assets,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:102400', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov'],
            'media_order' => ['nullable', 'array'],
            'media_order.*' => ['string', 'distinct', 'regex:/^(media|file):[0-9]+$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'category_ids' => $this->input('category_ids', []),
            'display_point_ids' => $this->input('display_point_ids', []),
            'media_asset_ids' => $this->input('media_asset_ids', []),
            'media_order' => $this->input('media_order', []),
        ]);
    }
}
