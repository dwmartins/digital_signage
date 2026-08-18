<?php

namespace App\Domains\Campaign\Controllers;

use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerSubscriptionController extends Controller
{
    /** Lista somente as assinaturas pertencentes ao anunciante autenticado. */
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== User::ROLE_CUSTOMER) {
            abort(403, 'Esta área é exclusiva para anunciantes.');
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in([
                CampaignSubscription::STATUS_PENDING,
                CampaignSubscription::STATUS_ACTIVE,
                CampaignSubscription::STATUS_EXPIRED,
                CampaignSubscription::STATUS_CANCELLED,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $baseQuery = CampaignSubscription::query()
            ->where('user_id', $request->user()->id);

        $summary = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $subscriptions = $baseQuery
            ->with([
                'campaign:id,name,status',
                'plan:id,name',
            ])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) ($validated['perPage'] ?? 6));

        return response()->json([
            'data' => $subscriptions->items(),
            'summary' => [
                'total' => $summary->sum(),
                'pending' => (int) ($summary[CampaignSubscription::STATUS_PENDING] ?? 0),
                'active' => (int) ($summary[CampaignSubscription::STATUS_ACTIVE] ?? 0),
                'expired' => (int) ($summary[CampaignSubscription::STATUS_EXPIRED] ?? 0),
                'cancelled' => (int) ($summary[CampaignSubscription::STATUS_CANCELLED] ?? 0),
            ],
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }
}
