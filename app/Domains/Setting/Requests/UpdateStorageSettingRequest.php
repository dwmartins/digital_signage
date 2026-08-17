<?php

namespace App\Domains\Setting\Requests;

use App\Domains\Setting\Models\StorageSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver' => ['required', Rule::in([
                StorageSetting::DRIVER_LOCAL,
                StorageSetting::DRIVER_R2,
                StorageSetting::DRIVER_S3,
            ])],
            'r2_account_id' => ['nullable', 'required_if:driver,r2', 'string', 'max:255'],
            'r2_access_key_id' => ['nullable', 'required_if:driver,r2', 'string', 'max:255'],
            'r2_secret_access_key' => ['nullable', 'string', 'max:1000'],
            'r2_bucket' => ['nullable', 'required_if:driver,r2', 'string', 'max:255'],
            'r2_endpoint' => ['nullable', 'url:http,https', 'max:500'],
            'aws_access_key_id' => ['nullable', 'required_if:driver,s3', 'string', 'max:255'],
            'aws_secret_access_key' => ['nullable', 'string', 'max:1000'],
            'aws_region' => ['nullable', 'required_if:driver,s3', 'string', 'max:64'],
            'aws_bucket' => ['nullable', 'required_if:driver,s3', 'string', 'max:255'],
            'aws_endpoint' => ['nullable', 'url:http,https', 'max:500'],
            'aws_url' => ['nullable', 'url:http,https', 'max:500'],
            'aws_use_path_style_endpoint' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'r2_account_id', 'r2_access_key_id', 'r2_bucket', 'r2_endpoint',
            'aws_access_key_id', 'aws_region', 'aws_bucket', 'aws_endpoint', 'aws_url',
        ];

        $this->merge(collect($fields)->mapWithKeys(fn (string $field) => [
            $field => $this->filled($field) ? trim((string) $this->input($field)) : null,
        ])->all());
    }
}
