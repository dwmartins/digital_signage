<?php

namespace App\Domains\Media\Models;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'campaign_id', 'media_asset_id', 'display_point_id', 'position', 'status',
    'processing_started_at', 'distributed_at', 'last_attempt_at', 'error_message',
])]
class MediaAssetDistribution extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DISPLAYED = 'displayed';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'processing_started_at' => 'datetime',
            'distributed_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function displayPoint(): BelongsTo
    {
        return $this->belongsTo(DisplayPoint::class);
    }
}
