<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Campaign\Models\CampaignSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDashboardController
{
    /** Retorna o estado inicial da dashboard do anunciante. */
    public function index(Request $request): JsonResponse
    {
        $hasActiveSubscription = CampaignSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('status', CampaignSubscription::STATUS_ACTIVE)
            ->where(fn ($query) => $query
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now()))
            ->exists();

        return response()->json([
            'has_active_subscription' => $hasActiveSubscription,
        ]);
    }
}
