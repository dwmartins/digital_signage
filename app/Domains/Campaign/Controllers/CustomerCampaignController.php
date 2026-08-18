<?php

namespace App\Domains\Campaign\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Models\MediaAssetDistribution;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerCampaignController extends Controller
{
    /** Lista as campanhas pertencentes ao anunciante autenticado. */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                Campaign::STATUS_ACTIVE,
                Campaign::STATUS_PENDING,
                Campaign::STATUS_PAUSED,
                Campaign::STATUS_CANCELLED,
            ])],
            'subscription_status' => ['nullable', Rule::in([
                CampaignSubscription::STATUS_PENDING,
                CampaignSubscription::STATUS_ACTIVE,
                CampaignSubscription::STATUS_EXPIRED,
                CampaignSubscription::STATUS_CANCELLED,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $customerId = $request->user()->id;
        $baseQuery = Campaign::query()->where('user_id', $customerId);
        $summary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', Campaign::STATUS_ACTIVE)->count(),
            'pending' => (clone $baseQuery)->where('status', Campaign::STATUS_PENDING)->count(),
            'paused' => (clone $baseQuery)->where('status', Campaign::STATUS_PAUSED)->count(),
            'cancelled' => (clone $baseQuery)->where('status', Campaign::STATUS_CANCELLED)->count(),
            'pending_media' => MediaAsset::query()
                ->where('user_id', $customerId)
                ->where('approval_status', MediaAsset::APPROVAL_PENDING)
                ->whereHas('campaigns', fn ($query) => $query->where('campaigns.user_id', $customerId))
                ->count(),
        ];

        $campaigns = $baseQuery
            ->with([
                'subscription:id,campaign_id,plan_id,status,price,billing_cycle,media_type,screen_limit,media_limit,starts_at,ends_at',
                'subscription.plan:id,name',
            ])
            ->withCount([
                'mediaAssets',
                'displayPoints',
                'mediaAssets as approved_media_count' => fn ($query) => $query
                    ->where('approval_status', MediaAsset::APPROVAL_APPROVED),
                'mediaAssets as pending_media_count' => fn ($query) => $query
                    ->where('approval_status', MediaAsset::APPROVAL_PENDING),
                'mediaDistributions as distribution_count',
                'mediaDistributions as displayed_distribution_count' => fn ($query) => $query
                    ->where('status', MediaAssetDistribution::STATUS_DISPLAYED),
                'mediaDistributions as failed_distribution_count' => fn ($query) => $query
                    ->where('status', MediaAssetDistribution::STATUS_FAILED),
            ])
            ->when($validated['global'] ?? null, fn ($query, $search) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('subscription.plan', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['subscription_status'] ?? null, fn ($query, $status) => $query
                ->whereHas('subscription', fn ($query) => $query->where('status', $status)))
            ->latest()
            ->paginate((int) ($validated['perPage'] ?? 6));

        return response()->json([
            'data' => $campaigns->items(),
            'summary' => $summary,
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    /** Retorna todos os detalhes de uma campanha do anunciante. */
    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'displayPoints.establishment.city.state:id,name,code',
                'displayPoints.establishment.neighborhood:id,city_id,name',
                'mediaAssets',
                'mediaDistributions',
                'subscription.plan',
            ])
            ->find($id);

        if (! $campaign) {
            return response()->json(['message' => 'Campanha não encontrada.'], 404);
        }

        $campaign->mediaAssets->each(fn (MediaAsset $media) => $media->setAttribute(
            'preview_url',
            url("/api/customer/campaigns/media/{$media->id}/content"),
        ));

        return response()->json(['campaign' => $campaign]);
    }

    /** Ativa ou pausa uma campanha pertencente ao anunciante. */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Campaign::STATUS_ACTIVE, Campaign::STATUS_PAUSED])],
        ]);
        $campaign = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->with('subscription')
            ->find($id);

        if (! $campaign) {
            return response()->json(['message' => 'Campanha não encontrada.'], 404);
        }

        if ($campaign->subscription?->status !== CampaignSubscription::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'A campanha somente pode ser ativada ou pausada enquanto a assinatura estiver ativa.',
            ], 422);
        }

        $oldStatus = $campaign->status;
        $campaign->update(['status' => $validated['status']]);
        AuditLogger::record(
            module: AuditLog::MODULE_CAMPAIGNS,
            action: AuditLog::ACTION_UPDATED,
            description: "Status da campanha {$campaign->name} alterado pelo anunciante.",
            auditable: $campaign,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $campaign->status],
            request: $request,
        );

        return response()->json([
            'message' => $campaign->status === Campaign::STATUS_ACTIVE
                ? 'Campanha ativada com sucesso.'
                : 'Campanha pausada com sucesso.',
            'campaign' => $campaign,
        ]);
    }

    /** Exibe uma mídia pertencente ao anunciante autenticado. */
    public function content(Request $request, int $id): mixed
    {
        $media = MediaAsset::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $media || ! Storage::disk($media->disk)->exists($media->path)) {
            return response()->json(['message' => 'Arquivo da mídia não encontrado.'], 404);
        }

        return Storage::disk($media->disk)->response(
            $media->path,
            $media->original_name,
            ['Content-Disposition' => 'inline'],
        );
    }
}
