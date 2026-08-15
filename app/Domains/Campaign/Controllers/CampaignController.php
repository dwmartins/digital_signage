<?php

namespace App\Domains\Campaign\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Campaign\Requests\CampaignRequest;
use App\Domains\Category\Models\Category;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Models\MediaAssetDistribution;
use App\Domains\Media\Services\MediaFileService;
use App\Domains\Media\Services\MediaHistoryLogger;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CampaignController extends Controller
{
    public function __construct(private readonly MediaFileService $mediaFileService) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'], 'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'status' => ['nullable', Rule::in($this->statuses())],
            'page' => ['nullable', 'integer', 'min:1'], 'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Campaign::query()->with(['customer:id,name,last_name,email', 'categories:id,name',
            'mediaAssets:id,user_id,name,type,original_name,mime_type,size_bytes', 'subscription.plan:id,name,billing_cycle']);
        if ($search = $validated['global'] ?? null) {
            $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
        }
        if ($value = $validated['user_id'] ?? null) {
            $query->where('user_id', $value);
        }
        if ($value = $validated['category_id'] ?? null) {
            $query->whereHas('categories', fn ($query) => $query->whereKey($value));
        }
        if ($value = $validated['status'] ?? null) {
            $query->where('status', $value);
        }
        $items = $query->latest()->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json(['data' => $items->items(), 'pagination' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name', 'last_name', 'email']),
            'categories' => Category::query()->where('status', Category::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']),
            'display_points' => DisplayPoint::query()
                ->where('status', DisplayPoint::STATUS_ACTIVE)
                ->whereHas('establishment', fn ($query) => $query->where('status', 'active'))
                ->with('establishment:id,name,address,number,neighborhood,city,state,zip_code,opening_hours')
                ->orderBy('name')
                ->get(['id', 'establishment_id', 'name', 'location']),
            'subscriptions' => CampaignSubscription::query()
                ->whereNull('campaign_id')
                ->whereIn('status', [CampaignSubscription::STATUS_PENDING, CampaignSubscription::STATUS_ACTIVE])
                ->with(['customer:id,name,last_name,email', 'plan:id,name'])
                ->latest()
                ->get(),
        ]);
    }

    public function mediaOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:campaign_subscriptions,id'],
        ]);
        $subscription = CampaignSubscription::query()->findOrFail($validated['subscription_id']);
        $media = MediaAsset::query()
            ->where('user_id', $subscription->user_id)
            ->where('type', $subscription->media_type)
            ->where('processing_status', MediaAsset::PROCESSING_READY)
            ->whereNotIn('approval_status', [MediaAsset::APPROVAL_REJECTED])
            ->latest()
            ->get(['id', 'name', 'original_name', 'type', 'size_bytes', 'width', 'height', 'duration_seconds', 'approval_status']);

        return response()->json(['data' => $media]);
    }

    public function displayPointOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'city' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = DisplayPoint::query()
            ->where('status', DisplayPoint::STATUS_ACTIVE)
            ->whereHas('establishment', fn ($query) => $query->where('status', 'active'))
            ->with('establishment:id,name,address,number,neighborhood,city,state,zip_code,opening_hours');

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('establishment', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('zip_code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('neighborhood', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%"));
            });
        }

        foreach (['state', 'city', 'neighborhood'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->whereHas('establishment', fn ($query) => $query->where($field, 'like', "%{$value}%"));
            }
        }

        $points = $query->orderBy('name')->paginate((int) ($validated['perPage'] ?? 5));

        return response()->json([
            'data' => $points->items(),
            'pagination' => [
                'current_page' => $points->currentPage(),
                'last_page' => $points->lastPage(),
                'per_page' => $points->perPage(),
                'total' => $points->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = Campaign::query()->with([
            'customer:id,name,last_name,email', 'categories:id,name', 'displayPoints.establishment', 'mediaAssets.approver:id,name,last_name',
            'mediaDistributions',
            'subscription.customer:id,name,last_name,email', 'subscription.plan',
        ])->find($id);

        return $campaign
            ? response()->json(['campaign' => $campaign])
            : $this->notFound();
    }

    public function store(CampaignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $path = null;
        try {
            $result = DB::transaction(function () use ($data, $request, &$path): array {
                $subscription = CampaignSubscription::query()->whereNull('campaign_id')->lockForUpdate()->find($data['subscription_id']);
                if (! $subscription) {
                    throw ValidationException::withMessages(['subscription_id' => 'Esta assinatura já possui uma campanha vinculada.']);
                }
                $this->validateDisplayPointLimit($data['display_point_ids'] ?? [], $subscription);
                $media = isset($data['media_asset_id'])
                    ? $this->libraryMedia($data['media_asset_id'], $subscription)
                    : null;

                if (! $media) {
                    $fileData = $this->mediaFileService->store($request->file('file'), $subscription->user_id);
                    $path = $fileData['path'];
                    if ($fileData['type'] !== $subscription->media_type) {
                        throw ValidationException::withMessages(['file' => 'O arquivo deve ser do tipo definido no plano da assinatura.']);
                    }
                    $media = MediaAsset::query()->create([...$fileData, 'user_id' => $subscription->user_id, 'uploaded_by' => $request->user()->id,
                        'name' => $data['name'], 'description' => $data['description'] ?? null, 'processing_status' => MediaAsset::PROCESSING_READY,
                        'approval_status' => MediaAsset::APPROVAL_PENDING]);
                }
                $campaign = Campaign::query()->create(['user_id' => $subscription->user_id, 'name' => $data['name'],
                    'description' => $data['description'] ?? null, 'status' => $data['status'] ?? Campaign::STATUS_ACTIVE]);
                $campaign->categories()->sync($data['category_ids'] ?? []);
                $campaign->displayPoints()->sync($data['display_point_ids'] ?? []);
                $campaign->mediaAssets()->sync([$media->id => ['position' => 1]]);
                $this->syncMediaDistributions($campaign, $media);
                $subscription->update(['campaign_id' => $campaign->id]);

                return compact('campaign', 'media');
            });
            $campaign = $result['campaign']->load(['customer', 'categories', 'mediaAssets', 'subscription.plan']);
            MediaHistoryLogger::record(
                media: $result['media'],
                event: isset($data['media_asset_id']) ? 'linked_to_campaign' : 'created',
                description: isset($data['media_asset_id'])
                    ? "Mídia {$result['media']->name} vinculada à campanha {$campaign->name}."
                    : "Mídia {$result['media']->name} enviada pela campanha {$campaign->name}.",
                newValues: $result['media']->toArray(),
                metadata: ['source' => isset($data['media_asset_id']) ? 'library' : 'campaign', 'campaign_id' => $campaign->id],
            );
            AuditLogger::record(
                module: AuditLog::MODULE_MEDIA,
                action: isset($data['media_asset_id']) ? AuditLog::ACTION_UPDATED : AuditLog::ACTION_CREATED,
                description: isset($data['media_asset_id'])
                    ? "Mídia {$result['media']->name} vinculada à campanha {$campaign->name}."
                    : "Mídia {$result['media']->name} enviada pela campanha {$campaign->name}.",
                auditable: $result['media'],
                newValues: $result['media']->toArray(),
                metadata: [
                    'event' => isset($data['media_asset_id']) ? 'linked_to_campaign' : 'created',
                    'source' => isset($data['media_asset_id']) ? 'library' : 'campaign',
                    'campaign_id' => $campaign->id,
                ],
                request: $request,
            );
            AuditLogger::record(module: AuditLog::MODULE_CAMPAIGNS, action: AuditLog::ACTION_CREATED, description: "Campanha {$campaign->name} criada e vinculada à assinatura #{$data['subscription_id']}.", auditable: $campaign, newValues: $campaign->toArray(), request: $request);

            return response()->json(['message' => 'Campanha e mídia criadas com sucesso.', 'campaign' => $campaign], 201);
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function update(CampaignRequest $request, int $id): JsonResponse
    {
        $campaign = Campaign::query()->with(['subscription', 'mediaAssets'])->find($id);
        if (! $campaign) {
            return $this->notFound();
        }
        $data = $request->validated();
        if ((int) $data['subscription_id'] !== (int) $campaign->subscription?->id) {
            return response()->json(['message' => 'A assinatura de uma campanha existente não pode ser alterada.'], 422);
        }
        $oldValues = $campaign->load(['categories', 'displayPoints', 'mediaAssets', 'subscription'])->toArray();
        $path = null;
        try {
            DB::transaction(function () use ($campaign, $data, $request, &$path): void {
                $this->validateDisplayPointLimit($data['display_point_ids'] ?? [], $campaign->subscription);
                $campaign->update([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'] ?? $campaign->status,
                ]);
                $campaign->categories()->sync($data['category_ids'] ?? []);
                $campaign->displayPoints()->sync($data['display_point_ids'] ?? []);
                if ($request->hasFile('file') || isset($data['media_asset_id'])) {
                    if (isset($data['media_asset_id'])) {
                        $media = $this->libraryMedia($data['media_asset_id'], $campaign->subscription);
                    }

                    if ($request->hasFile('file')) {
                        $fileData = $this->mediaFileService->store($request->file('file'), $campaign->user_id);
                        $path = $fileData['path'];
                        if ($fileData['type'] !== $campaign->subscription->media_type) {
                            throw ValidationException::withMessages(['file' => 'O arquivo deve ser do tipo definido no plano da assinatura.']);
                        }
                        $media = MediaAsset::query()->create([...$fileData, 'user_id' => $campaign->user_id, 'uploaded_by' => $request->user()->id,
                            'name' => $data['name'], 'description' => $data['description'] ?? null, 'processing_status' => MediaAsset::PROCESSING_READY,
                            'approval_status' => MediaAsset::APPROVAL_PENDING]);
                        MediaHistoryLogger::record(
                            media: $media,
                            event: 'created',
                            description: "Mídia {$media->name} enviada na substituição da campanha {$campaign->name}.",
                            newValues: $media->toArray(),
                            metadata: ['source' => 'campaign_replacement', 'campaign_id' => $campaign->id],
                        );
                        AuditLogger::record(
                            module: AuditLog::MODULE_MEDIA,
                            action: AuditLog::ACTION_CREATED,
                            description: "Mídia {$media->name} enviada na substituição da campanha {$campaign->name}.",
                            auditable: $media,
                            newValues: $media->toArray(),
                            metadata: ['event' => 'created', 'source' => 'campaign_replacement', 'campaign_id' => $campaign->id],
                            request: $request,
                        );
                    }

                    $campaign->mediaAssets()->sync([$media->id => ['position' => 1]]);

                    if (isset($data['media_asset_id'])) {
                        MediaHistoryLogger::record(
                            media: $media,
                            event: 'linked_to_campaign',
                            description: "Mídia {$media->name} vinculada à campanha {$campaign->name}.",
                            newValues: ['campaign_id' => $campaign->id],
                            metadata: ['source' => 'library', 'campaign_id' => $campaign->id],
                        );
                    }
                }

                $this->syncMediaDistributions(
                    $campaign,
                    $campaign->mediaAssets()->first(),
                );

            });
            $campaign->refresh()->load(['customer', 'categories', 'displayPoints.establishment', 'mediaAssets', 'subscription.plan']);
            AuditLogger::record(module: AuditLog::MODULE_CAMPAIGNS, action: AuditLog::ACTION_UPDATED, description: "Campanha {$campaign->name} atualizada.", auditable: $campaign, oldValues: $oldValues, newValues: $campaign->toArray(), request: $request);

            return response()->json(['message' => 'Campanha atualizada com sucesso.', 'campaign' => $campaign]);
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = Campaign::query()->with('subscription.invoices')->find($id);
        if (! $campaign) {
            return $this->notFound();
        }
        if ($campaign->subscription?->invoices->isNotEmpty()) {
            return response()->json(['message' => 'Não é possível excluir uma campanha que possui histórico financeiro.'], 422);
        }
        AuditLogger::record(module: AuditLog::MODULE_CAMPAIGNS, action: AuditLog::ACTION_DELETED, description: "Campanha {$campaign->name} excluída.", auditable: $campaign, oldValues: $campaign->toArray());
        $campaign->subscription?->update(['campaign_id' => null]);
        $campaign->delete();

        return response()->json(['message' => 'Campanha excluída com sucesso.']);
    }

    public function detachMedia(Request $request, int $id, int $mediaId): JsonResponse
    {
        $campaign = Campaign::query()->with('mediaAssets')->find($id);
        if (! $campaign) {
            return $this->notFound();
        }

        $media = $campaign->mediaAssets->firstWhere('id', $mediaId);
        if (! $media) {
            return response()->json(['message' => 'A mídia não está vinculada a esta campanha.'], 404);
        }

        DB::transaction(function () use ($campaign, $media, $request): void {
            $campaign->mediaAssets()->detach($media->id);
            $this->syncMediaDistributions($campaign, null);

            MediaHistoryLogger::record(
                media: $media,
                event: 'detached_from_campaign',
                description: "Mídia {$media->name} desvinculada da campanha {$campaign->name}.",
                oldValues: ['campaign_id' => $campaign->id],
                newValues: ['campaign_id' => null],
                metadata: ['campaign_id' => $campaign->id, 'campaign_name' => $campaign->name],
                user: $request->user(),
            );
        });

        return response()->json([
            'message' => 'Mídia desvinculada. A campanha agora está sem conteúdo.',
            'campaign' => $campaign->fresh()->load(['customer', 'categories', 'mediaAssets', 'subscription.customer', 'subscription.plan']),
        ]);
    }

    private function statuses(): array
    {
        return [Campaign::STATUS_ACTIVE, Campaign::STATUS_INACTIVE];
    }

    private function libraryMedia(int $mediaId, CampaignSubscription $subscription): MediaAsset
    {
        $media = MediaAsset::query()
            ->where('user_id', $subscription->user_id)
            ->where('type', $subscription->media_type)
            ->where('processing_status', MediaAsset::PROCESSING_READY)
            ->whereNotIn('approval_status', [MediaAsset::APPROVAL_REJECTED])
            ->find($mediaId);

        if (! $media) {
            throw ValidationException::withMessages([
                'media_asset_id' => 'A mídia selecionada não pertence ao anunciante ou não é compatível com o plano.',
            ]);
        }

        return $media;
    }

    private function validateDisplayPointLimit(array $displayPointIds, CampaignSubscription $subscription): void
    {
        if (count($displayPointIds) > $subscription->screen_limit) {
            throw ValidationException::withMessages([
                'display_point_ids' => "O plano permite no máximo {$subscription->screen_limit} ponto(s) de exibição.",
            ]);
        }

        $availableCount = DisplayPoint::query()
            ->whereIn('id', $displayPointIds)
            ->where('status', DisplayPoint::STATUS_ACTIVE)
            ->whereHas('establishment', fn ($query) => $query->where('status', 'active'))
            ->count();

        if ($availableCount !== count($displayPointIds)) {
            throw ValidationException::withMessages([
                'display_point_ids' => 'Um ou mais pontos selecionados não estão disponíveis.',
            ]);
        }
    }

    private function syncMediaDistributions(Campaign $campaign, ?MediaAsset $media): void
    {
        $distributionQuery = MediaAssetDistribution::query()
            ->where('campaign_id', $campaign->id);

        $displayPointIds = $campaign->displayPoints()
            ->pluck('display_points.id');

        if (! $media || $displayPointIds->isEmpty()) {
            $distributionQuery->delete();

            return;
        }

        (clone $distributionQuery)
            ->where(function ($query) use ($media, $displayPointIds): void {
                $query->where('media_asset_id', '!=', $media->id)
                    ->orWhereNotIn('display_point_id', $displayPointIds);
            })
            ->delete();

        $displayPointIds->each(function (int $displayPointId) use ($campaign, $media): void {
            MediaAssetDistribution::query()->firstOrCreate([
                'campaign_id' => $campaign->id,
                'media_asset_id' => $media->id,
                'display_point_id' => $displayPointId,
            ], [
                'status' => MediaAssetDistribution::STATUS_PENDING,
            ]);
        });
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Campanha não encontrada.'], 404);
    }
}
