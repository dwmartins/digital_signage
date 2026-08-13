<?php

namespace App\Domains\Campaign\Models;

use App\Domains\Category\Models\Category;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'approved_by', 'name', 'description', 'status', 'approved_at', 'rejection_reason'])]
class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class)
            ->withPivot(['position', 'display_duration_seconds'])
            ->withTimestamps();
    }

    public function displayPoints(): BelongsToMany
    {
        return $this->belongsToMany(DisplayPoint::class)->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CampaignSubscription::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CampaignSubscription::class);
    }
}
