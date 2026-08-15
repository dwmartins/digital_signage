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

        $newStatus = self::hasActiveSubscription($media)
            ? MediaAsset::APPROVAL_APPROVED
            : MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION;

        if ($media->approval_status === $newStatus) {
            return;
        }

        $oldValues = $media->toArray();
        $media->update(['approval_status' => $newStatus]);

        MediaHistoryLogger::record(
            media: $media,
            event: 'subscription_status_changed',
            description: $newStatus === MediaAsset::APPROVAL_APPROVED
                ? "Mídia {$media->name} liberada após ativação da assinatura."
                : "Mídia {$media->name} aguardando assinatura ativa.",
            oldValues: $oldValues,
            newValues: $media->fresh()->toArray(),
        );
    }

    private static function hasActiveSubscription(MediaAsset $media): bool
    {
        return $media->campaigns()
            ->whereHas('subscription', fn ($query) => $query
                ->where('status', CampaignSubscription::STATUS_ACTIVE))
            ->exists();
    }
}
