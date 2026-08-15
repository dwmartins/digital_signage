<?php

namespace App\Domains\Plan\Models;

use App\Domains\Campaign\Models\CampaignSubscription;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'screen_limit', 'media_limit', 'billing_cycle', 'media_type', 'price', 'status'])]
class Plan extends Model
{
    public const BILLING_MONTHLY = 'monthly';

    public const BILLING_YEARLY = 'yearly';

    public const MEDIA_IMAGE = 'image';

    public const MEDIA_VIDEO = 'video';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected function casts(): array
    {
        return [
            'screen_limit' => 'integer',
            'media_limit' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CampaignSubscription::class);
    }
}
