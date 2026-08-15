<?php

namespace App\Domains\Media\Models;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'uploaded_by', 'name', 'description', 'type', 'original_name',
    'disk', 'path', 'mime_type', 'extension', 'size_bytes', 'width', 'height',
    'duration_seconds', 'orientation', 'checksum', 'processing_status',
    'processing_error', 'approval_status', 'approved_by', 'approved_at',
    'rejection_reason',
])]
#[Appends(['content_url', 'availability_status'])]
class MediaAsset extends Model
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    public const PROCESSING_PROCESSING = 'processing';
    public const PROCESSING_READY = 'ready';
    public const PROCESSING_FAILED = 'failed';

    public const APPROVAL_PENDING = 'pending_approval';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_AWAITING_SUBSCRIPTION = 'awaiting_subscription';

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function scopeAvailableForCampaign(Builder $query): Builder
    {
        return $query
            ->where('processing_status', self::PROCESSING_READY)
            ->whereIn('approval_status', [
                self::APPROVAL_AWAITING_SUBSCRIPTION,
                self::APPROVAL_APPROVED,
            ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->withPivot(['position', 'display_duration_seconds'])
            ->withTimestamps();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MediaAssetHistory::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(MediaAssetDistribution::class);
    }

    public function getContentUrlAttribute(): string
    {
        return url("/api/media-assets/{$this->id}/content");
    }

    public function getAvailabilityStatusAttribute(): string
    {
        if ($this->processing_status !== self::PROCESSING_READY) {
            return 'processing';
        }

        if ($this->approval_status === self::APPROVAL_PENDING) {
            return 'pending_approval';
        }

        if ($this->approval_status === self::APPROVAL_REJECTED) {
            return 'rejected';
        }

        $campaignsCount = $this->getAttribute('campaigns_count')
            ?? $this->campaigns()->count();

        if (! $campaignsCount) {
            return 'without_campaign';
        }

        if ($this->approval_status !== self::APPROVAL_APPROVED) {
            return 'linked';
        }

        $isDisplayed = $this->distributions()
            ->where('status', MediaAssetDistribution::STATUS_DISPLAYED)
            ->exists();

        return $isDisplayed ? 'available' : 'linked';
    }
}
