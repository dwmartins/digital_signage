<?php

namespace App\Domains\Media\Services;

use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Media\Models\MediaAsset;

class MediaStatusService
{
    public static function markAsReviewed(MediaAsset $media, int $reviewerId): void
    {
        $media->update([
            'approval_status' => self::hasActiveSubscription($media)
                ? MediaAsset::APPROVAL_APPROVED
                : MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
            'approved_by' => $reviewerId,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public static function syncSubscriptionStatus(MediaAsset $media): void
    {
        if (! in_array($media->approval_status, [
            MediaAsset::APPROVAL_APPROVED,
            MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
        ], true)) {
            return;
        }

        $media->update([
            'approval_status' => self::hasActiveSubscription($media)
                ? MediaAsset::APPROVAL_APPROVED
                : MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
        ]);
    }

    private static function hasActiveSubscription(MediaAsset $media): bool
    {
        return $media->campaigns()
            ->whereHas('subscription', fn ($query) => $query
                ->where('status', CampaignSubscription::STATUS_ACTIVE))
            ->exists();
    }
}
