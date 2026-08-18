<?php

namespace App\Domains\Campaign\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Campaign\Requests\CampaignRequest;
use App\Domains\Campaign\Services\CampaignStatusService;
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
                ->with([
                    'establishment.city.state:id,name,code',
                    'establishment.neighborhood:id,city_id,name',
                ])
                ->orderBy('name')
                ->get(['id', 'establishment_id', 'name', 'location', 'orientation']),
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
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = DisplayPoint::query()
            ->where('status', DisplayPoint::STATUS_ACTIVE)
            ->whereHas('establishment', fn ($query) => $query->where('status', 'active'))
            ->with([
                'establishment.city.state:id,name,code',
                'establishment.neighborhood:id,city_id,name',
            ]);

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('establishment', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('zip_code', 'like', "%{$search}%")
                        ->orWhereHas('city', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('neighborhood', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('city.state', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")));
            });
        }

        foreach (['city_id', 'neighborhood_id'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->whereHas('establishment', fn ($query) => $query->where($field, $value));
            }
        }

        if ($stateId = $validated['state_id'] ?? null) {
            $query->whereHas('establishment.city', fn ($query) => $query->where('state_id', $stateId));
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
            'customer:id,name,last_name,email', 'categories:id,name',
            'displayPoints.establishment.city.state:id,name,code',
            'displayPoints.establishment.neighborhood:id,city_id,name',
            'mediaAssets.approver:id,name,last_name',
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
        $paths = [];

        try {
            $result = DB::transaction(function () use ($data, $request, &$paths): array {
                $subscription = CampaignSubscription::query()->whereNull('campaign_id')->lockForUpdate()->find($data['subscription_id']);

                if (! $subscription) {
                    throw ValidationException::withMessages(['subscription_id' => 'Esta assinatura já possui uma campanha vinculada.']);
                }

                $this->validateDisplayPointLimit($data['display_point_ids'] ?? [], $subscription);

                $libraryMedia = collect($data['media_asset_ids'] ?? [])
                    ->map(fn (int $mediaId) => $this->libraryMedia($mediaId, $subscription));
                $files = collect($request->file('files', []));

                if ($libraryMedia->isEmpty() && $files->isEmpty()) {
                    throw ValidationException::withMessages([
                        'files' => 'Envie ao menos um arquivo ou selecione uma mídia da Biblioteca.',
                    ]);
                }

                $this->validateMediaLimit($libraryMedia->count() + $files->count(), $subscription);

                $uploadedMedia = $files->map(function ($file) use ($subscription, $data, $request, &$paths): MediaAsset {
                    $fileData = $this->mediaFileService->store($file, $subscription->user_id);
                    $paths[] = ['disk' => $fileData['disk'], 'path' => $fileData['path']];
                    $this->validateMediaType($fileData['type'], $subscription);

                    return MediaAsset::query()->create([
                        ...$fileData,
                        'user_id' => $subscription->user_id,
                        'uploaded_by' => $request->user()->id,
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'processing_status' => MediaAsset::PROCESSING_READY,
                        'approval_status' => MediaAsset::APPROVAL_PENDING,
                    ]);
                });
                $mediaAssets = $libraryMedia->concat($uploadedMedia)->values();

                $campaign = Campaign::query()->create(['user_id' => $subscription->user_id, 'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'playback_mode' => $this->playbackMode($data, $subscription),
                    'status' => $this->campaignStatus($subscription, $data['status'] ?? null)]);
                $campaign->categories()->sync($data['category_ids'] ?? []);
                $campaign->displayPoints()->sync($data['display_point_ids'] ?? []);
                $campaign->mediaAssets()->sync($mediaAssets->mapWithKeys(
                    fn (MediaAsset $media, int $index) => [$media->id => ['position' => $index + 1]],
                ));
                $this->applyMediaOrder($campaign, $data['media_order'] ?? [], $uploadedMedia);
                $this->syncMediaDistributions($campaign, $mediaAssets);
                $subscription->update(['campaign_id' => $campaign->id]);

                return compact('campaign', 'libraryMedia', 'uploadedMedia');
            });
            $campaign = $result['campaign']->load(['customer', 'categories', 'mediaAssets', 'subscription.plan']);
            $result['libraryMedia']->each(fn (MediaAsset $media) => $this->recordMediaAdded($media, $campaign, 'library', $request));
            $result['uploadedMedia']->each(fn (MediaAsset $media) => $this->recordMediaAdded($media, $campaign, 'campaign', $request));
            AuditLogger::record(module: AuditLog::MODULE_CAMPAIGNS, action: AuditLog::ACTION_CREATED, description: "Campanha {$campaign->name} criada e vinculada à assinatura #{$data['subscription_id']}.", auditable: $campaign, newValues: $campaign->toArray(), request: $request);

            return response()->json(['message' => 'Campanha e mídias criadas com sucesso.', 'campaign' => $campaign], 201);
        } catch (Throwable $exception) {
            collect($paths)->groupBy('disk')->each(
                fn ($files, string $disk) => Storage::disk($disk)->delete($files->pluck('path')->all()),
            );
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
        $paths = [];

        try {
            $addedMedia = DB::transaction(function () use ($campaign, $data, $request, &$paths) {
                $this->validateDisplayPointLimit($data['display_point_ids'] ?? [], $campaign->subscription);
                $campaign->update([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'playback_mode' => $this->playbackMode($data, $campaign->subscription),
                    'status' => $this->campaignStatus(
                        $campaign->subscription,
                        $data['status'] ?? null,
                        $campaign->status,
                    ),
                ]);
                $campaign->categories()->sync($data['category_ids'] ?? []);
                $campaign->displayPoints()->sync($data['display_point_ids'] ?? []);
                $existingIds = $campaign->mediaAssets()->pluck('media_assets.id');
                $libraryMedia = collect($data['media_asset_ids'] ?? [])
                    ->unique()
                    ->reject(fn (int $mediaId) => $existingIds->contains($mediaId))
                    ->map(fn (int $mediaId) => $this->libraryMedia($mediaId, $campaign->subscription));
                $files = collect($request->file('files', []));
                $this->validateMediaLimit($existingIds->count() + $libraryMedia->count() + $files->count(), $campaign->subscription);

                $uploadedMedia = $files->map(function ($file) use ($campaign, $data, $request, &$paths): MediaAsset {
                    $fileData = $this->mediaFileService->store($file, $campaign->user_id);
                    $paths[] = ['disk' => $fileData['disk'], 'path' => $fileData['path']];
                    $this->validateMediaType($fileData['type'], $campaign->subscription);

                    return MediaAsset::query()->create([
                        ...$fileData,
                        'user_id' => $campaign->user_id,
                        'uploaded_by' => $request->user()->id,
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'processing_status' => MediaAsset::PROCESSING_READY,
                        'approval_status' => MediaAsset::APPROVAL_PENDING,
                    ]);
                });
                $newMedia = $libraryMedia->concat($uploadedMedia)->values();
                $position = $existingIds->count();
                $campaign->mediaAssets()->syncWithoutDetaching($newMedia->mapWithKeys(
                    fn (MediaAsset $media, int $index) => [$media->id => ['position' => $position + $index + 1]],
                ));
                $this->applyMediaOrder($campaign, $data['media_order'] ?? [], $uploadedMedia);
                $this->syncMediaDistributions($campaign, $campaign->mediaAssets()->get());

                return ['library' => $libraryMedia, 'uploaded' => $uploadedMedia];
            });

            $campaign->refresh()->load(['customer', 'categories', 'displayPoints.establishment', 'mediaAssets', 'subscription.plan']);
            $addedMedia['library']->each(fn (MediaAsset $media) => $this->recordMediaAdded($media, $campaign, 'library', $request));
            $addedMedia['uploaded']->each(fn (MediaAsset $media) => $this->recordMediaAdded($media, $campaign, 'campaign', $request));
            AuditLogger::record(module: AuditLog::MODULE_CAMPAIGNS, action: AuditLog::ACTION_UPDATED, description: "Campanha {$campaign->name} atualizada.", auditable: $campaign, oldValues: $oldValues, newValues: $campaign->toArray(), request: $request);

            return response()->json(['message' => 'Campanha atualizada com sucesso.', 'campaign' => $campaign]);
        } catch (Throwable $exception) {
            collect($paths)->groupBy('disk')->each(
                fn ($files, string $disk) => Storage::disk($disk)->delete($files->pluck('path')->all()),
            );
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
            $this->applyMediaOrder($campaign, []);
            $this->syncMediaDistributions($campaign, $campaign->mediaAssets()->get());

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
            'message' => 'Mídia desvinculada da campanha com sucesso.',
            'campaign' => $campaign->fresh()->load(['customer', 'categories', 'mediaAssets', 'subscription.customer', 'subscription.plan']),
        ]);
    }

    private function statuses(): array
    {
        return [
            Campaign::STATUS_ACTIVE,
            Campaign::STATUS_PENDING,
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_CANCELLED,
        ];
    }

    private function campaignStatus(
        CampaignSubscription $subscription,
        ?string $requestedStatus,
        string $currentStatus = Campaign::STATUS_ACTIVE,
    ): string {
        if ($subscription->status !== CampaignSubscription::STATUS_ACTIVE) {
            return CampaignStatusService::forSubscription($subscription->status);
        }

        if (in_array($requestedStatus, [Campaign::STATUS_ACTIVE, Campaign::STATUS_PAUSED], true)) {
            return $requestedStatus;
        }

        return in_array($currentStatus, [Campaign::STATUS_ACTIVE, Campaign::STATUS_PAUSED], true)
            ? $currentStatus
            : Campaign::STATUS_ACTIVE;
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
                'media_asset_ids' => 'Uma das mídias selecionadas não pertence ao anunciante ou não é compatível com o plano.',
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

    private function validateMediaLimit(int $mediaCount, CampaignSubscription $subscription): void
    {
        if ($mediaCount > $subscription->media_limit) {
            throw ValidationException::withMessages([
                'media_asset_ids' => "O plano permite no máximo {$subscription->media_limit} mídia(s) por campanha.",
            ]);
        }
    }

    private function playbackMode(array $data, CampaignSubscription $subscription): string
    {
        if ($subscription->media_limit <= 1) {
            return Campaign::PLAYBACK_SEQUENTIAL;
        }

        return $data['playback_mode'] ?? Campaign::PLAYBACK_SEQUENTIAL;
    }

    private function applyMediaOrder(Campaign $campaign, array $order, iterable $uploadedMedia = []): void
    {
        $currentMediaIds = $campaign->mediaAssets()
            ->pluck('media_assets.id');
        $fileKeys = collect($uploadedMedia)
            ->values()
            ->mapWithKeys(fn (MediaAsset $media, int $index) => ["file:{$index}" => $media->id]);
        $mediaKeys = $currentMediaIds
            ->mapWithKeys(fn (int $mediaId) => ["media:{$mediaId}" => $mediaId]);
        $keyMap = $mediaKeys->merge($fileKeys);
        $orderedIds = collect($order)
            ->map(fn (string $key) => $keyMap->get($key))
            ->filter(fn ($mediaId) => $mediaId && $currentMediaIds->contains($mediaId))
            ->unique()
            ->values();
        $orderedIds = $orderedIds
            ->concat($currentMediaIds->diff($orderedIds))
            ->values();

        $orderedIds->each(fn (int $mediaId, int $index) => $campaign->mediaAssets()
            ->updateExistingPivot($mediaId, ['position' => $index + 1]));
    }

    private function validateMediaType(string $mediaType, CampaignSubscription $subscription): void
    {
        if ($mediaType !== $subscription->media_type) {
            throw ValidationException::withMessages([
                'files' => 'Todos os arquivos devem ser do tipo definido no plano da assinatura.',
            ]);
        }
    }

    private function recordMediaAdded(MediaAsset $media, Campaign $campaign, string $source, Request $request): void
    {
        $fromLibrary = $source === 'library';
        $event = $fromLibrary ? 'linked_to_campaign' : 'created';
        $description = $fromLibrary
            ? "Mídia {$media->name} vinculada à campanha {$campaign->name}."
            : "Mídia {$media->name} enviada pela campanha {$campaign->name}.";

        MediaHistoryLogger::record(
            media: $media,
            event: $event,
            description: $description,
            newValues: $media->toArray(),
            metadata: ['source' => $source, 'campaign_id' => $campaign->id],
            user: $request->user(),
        );
        AuditLogger::record(
            module: AuditLog::MODULE_MEDIA,
            action: $fromLibrary ? AuditLog::ACTION_UPDATED : AuditLog::ACTION_CREATED,
            description: $description,
            auditable: $media,
            newValues: $media->toArray(),
            metadata: ['event' => $event, 'source' => $source, 'campaign_id' => $campaign->id],
            request: $request,
        );
    }

    private function syncMediaDistributions(Campaign $campaign, iterable $mediaAssets): void
    {
        $distributionQuery = MediaAssetDistribution::query()
            ->where('campaign_id', $campaign->id);

        $displayPointIds = $campaign->displayPoints()
            ->pluck('display_points.id');
        $mediaIds = collect($mediaAssets)->pluck('id');

        if ($mediaIds->isEmpty() || $displayPointIds->isEmpty()) {
            $distributionQuery->delete();

            return;
        }

        (clone $distributionQuery)
            ->where(function ($query) use ($mediaIds, $displayPointIds): void {
                $query->whereNotIn('media_asset_id', $mediaIds)
                    ->orWhereNotIn('display_point_id', $displayPointIds);
            })
            ->delete();

        $mediaIds->each(function (int $mediaId) use ($campaign, $displayPointIds): void {
            $displayPointIds->each(function (int $displayPointId) use ($campaign, $mediaId): void {
                MediaAssetDistribution::query()->firstOrCreate([
                    'campaign_id' => $campaign->id,
                    'media_asset_id' => $mediaId,
                    'display_point_id' => $displayPointId,
                ], [
                    'status' => MediaAssetDistribution::STATUS_PENDING,
                ]);
            });
        });
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Campanha não encontrada.'], 404);
    }
}
