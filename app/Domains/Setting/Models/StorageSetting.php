<?php

namespace App\Domains\Setting\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'key', 'driver', 'r2_account_id', 'r2_access_key_id',
    'r2_secret_access_key', 'r2_bucket', 'r2_endpoint',
    'aws_access_key_id', 'aws_secret_access_key', 'aws_region',
    'aws_bucket', 'aws_endpoint', 'aws_url',
    'aws_use_path_style_endpoint', 'updated_by',
])]
#[Hidden(['r2_secret_access_key', 'aws_secret_access_key'])]
class StorageSetting extends Model
{
    public const DRIVER_LOCAL = 'local';

    public const DRIVER_R2 = 'r2';

    public const DRIVER_S3 = 's3';

    protected function casts(): array
    {
        return [
            'r2_secret_access_key' => 'encrypted',
            'aws_secret_access_key' => 'encrypted',
            'aws_use_path_style_endpoint' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
