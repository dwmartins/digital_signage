<?php

namespace App\Domains\Campaign\Models;

use App\Domains\Category\Models\Category;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Models\MediaAssetDistribution;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'description', 'playback_mode', 'status'])]
class Campaign extends Model
{
    public const PLAYBACK_SEQUENTIAL = 'sequential';

    public const PLAYBACK_RANDOM = 'random';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class)
            ->withPivot(['position', 'display_duration_seconds'])
            ->orderByPivot('position')
            ->withTimestamps();
    }

    public function displayPoints(): BelongsToMany
    {
        return $this->belongsToMany(DisplayPoint::class)->withTimestamps();
    }

    public function mediaDistributions(): HasMany
    {
        return $this->hasMany(MediaAssetDistribution::class);
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
