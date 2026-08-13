<?php

namespace App\Domains\Media\Requests;

use App\Domains\Media\Models\MediaAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_status' => ['required', Rule::in([
                MediaAsset::APPROVAL_APPROVED,
                MediaAsset::APPROVAL_REJECTED,
            ])],
            'rejection_reason' => [
                Rule::requiredIf($this->input('approval_status') === MediaAsset::APPROVAL_REJECTED),
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rejection_reason' => $this->filled('rejection_reason')
                ? trim((string) $this->input('rejection_reason'))
                : null,
        ]);
    }
}
