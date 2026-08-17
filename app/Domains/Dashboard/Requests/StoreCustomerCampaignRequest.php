<?php

namespace App\Domains\Dashboard\Requests;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_CUSTOMER;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where('status', Category::STATUS_ACTIVE),
            ],
            'display_point_ids' => ['required', 'array', 'min:1'],
            'display_point_ids.*' => ['integer', 'distinct', 'exists:display_points,id'],
            'playback_mode' => ['required', Rule::in([
                Campaign::PLAYBACK_SEQUENTIAL,
                Campaign::PLAYBACK_RANDOM,
            ])],
            'media_asset_ids' => ['required_without:files', 'array'],
            'media_asset_ids.*' => ['integer', 'distinct', 'exists:media_assets,id'],
            'files' => ['required_without:media_asset_ids', 'array'],
            'files.*' => ['required', 'file', 'max:102400', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov'],
            'media_order' => ['nullable', 'array'],
            'media_order.*' => ['string', 'distinct', 'regex:/^(library:[0-9]+|file:[0-9]+)$/'],
            'media_assignments' => ['required', 'array'],
            'media_assignments.*' => ['required', 'array', 'min:1'],
            'media_assignments.*.*' => ['integer'],
            'display_orders' => ['nullable', 'array'],
            'display_orders.*' => ['array'],
            'display_orders.*.*' => ['string', 'regex:/^(library:[0-9]+|file:[0-9]+)$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mediaAssignments = $this->input('media_assignments', []);

        if (is_string($mediaAssignments)) {
            $mediaAssignments = json_decode($mediaAssignments, true) ?? [];
        }

        $displayOrders = $this->input('display_orders', []);

        if (is_string($displayOrders)) {
            $displayOrders = json_decode($displayOrders, true) ?? [];
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : null,
            'category_ids' => $this->input('category_ids', []),
            'display_point_ids' => $this->input('display_point_ids', []),
            'media_asset_ids' => $this->input('media_asset_ids', []),
            'media_order' => $this->input('media_order', []),
            'media_assignments' => $mediaAssignments,
            'display_orders' => $displayOrders,
        ]);
    }
}
