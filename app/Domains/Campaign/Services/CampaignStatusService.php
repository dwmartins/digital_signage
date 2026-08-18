<?php

namespace App\Domains\Campaign\Services;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;

class CampaignStatusService
{
    /** Sincroniza a campanha com a situação atual da assinatura vinculada. */
    public static function sync(CampaignSubscription $subscription): void
    {
        if (! $subscription->campaign_id) {
            return;
        }

        $subscription->campaign()->update([
            'status' => self::forSubscription($subscription->status),
        ]);
    }

    /** Retorna o status obrigatório da campanha para uma assinatura. */
    public static function forSubscription(string $subscriptionStatus): string
    {
        return match ($subscriptionStatus) {
            CampaignSubscription::STATUS_PENDING => Campaign::STATUS_PENDING,
            CampaignSubscription::STATUS_EXPIRED => Campaign::STATUS_PAUSED,
            CampaignSubscription::STATUS_CANCELLED => Campaign::STATUS_CANCELLED,
            default => Campaign::STATUS_ACTIVE,
        };
    }
}
