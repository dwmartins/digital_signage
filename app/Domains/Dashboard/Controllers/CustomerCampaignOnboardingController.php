<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Category\Models\Category;
use App\Domains\Dashboard\Requests\StoreCustomerCampaignRequest;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Establishment\Models\Establishment;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Models\MediaAssetDistribution;
use App\Domains\Media\Services\MediaFileService;
use App\Domains\Plan\Models\Plan;
use App\Domains\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerCampaignOnboardingController
{
    public function __construct(private readonly MediaFileService $mediaFileService)
    {
    }

    /** Retorna os planos e categorias disponíveis para a contratação. */
    public function options(Request $request): JsonResponse
    {
        if ($request->user()->role !== User::ROLE_CUSTOMER) {
            abort(403, 'Esta área é exclusiva para anunciantes.');
        }

        return response()->json([
            'plans' => Plan::query()
                ->where('status', Plan::STATUS_ACTIVE)
                ->orderBy('price')
                ->get(),
            'categories' => Category::query()
                ->where('status', Category::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name']),
            'display_points' => DisplayPoint::query()
                ->with([
                    'establishment.city.state:id,name,code',
                    'establishment.neighborhood:id,name',
                ])
                ->where('status', DisplayPoint::STATUS_ACTIVE)
                ->whereHas('establishment', fn ($query) => $query
                    ->where('status', Establishment::STATUS_ACTIVE))
                ->orderBy('name')
                ->get([
                    'id', 'establishment_id', 'name', 'location', 'orientation',
                ]),
            'media_assets' => MediaAsset::query()
                ->where('user_id', $request->user()->id)
                ->where('processing_status', MediaAsset::PROCESSING_READY)
                ->whereIn('approval_status', [
                    MediaAsset::APPROVAL_PENDING,
                    MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
                    MediaAsset::APPROVAL_APPROVED,
                ])
                ->latest()
                ->get([
                    'id', 'name', 'original_name', 'type', 'mime_type', 'size_bytes',
                    'width', 'height', 'duration_seconds', 'orientation', 'approval_status',
                ])
                ->map(fn (MediaAsset $media) => [
                    ...$media->toArray(),
                    'preview_url' => url("/api/customer/campaign-onboarding/media/{$media->id}/content"),
                ]),
        ]);
    }

    /** Exibe uma mídia da biblioteca pertencente ao anunciante autenticado. */
    public function content(Request $request, int $id): mixed
    {
        if ($request->user()->role !== User::ROLE_CUSTOMER) {
            abort(403, 'Esta área é exclusiva para anunciantes.');
        }

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

    /** Cria a contratação pendente, a campanha e suas mídias. */
    public function store(StoreCustomerCampaignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $customerId = $request->user()->id;
        $files = collect($request->file('files', []));
        $libraryMedia = MediaAsset::query()
            ->where('user_id', $customerId)
            ->whereIn('id', $data['media_asset_ids'] ?? [])
            ->where('processing_status', MediaAsset::PROCESSING_READY)
            ->whereIn('approval_status', [
                MediaAsset::APPROVAL_PENDING,
                MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
                MediaAsset::APPROVAL_APPROVED,
            ])
            ->get();
        $storedFiles = [];

        $plan = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->find($data['plan_id']);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => 'O plano selecionado não está mais disponível.',
            ]);
        }

        if ($libraryMedia->count() !== count($data['media_asset_ids'] ?? [])) {
            throw ValidationException::withMessages([
                'media_asset_ids' => 'Uma das mídias selecionadas não está disponível na sua Biblioteca.',
            ]);
        }

        if ($libraryMedia->contains(fn (MediaAsset $media) => $media->type !== $plan->media_type)) {
            throw ValidationException::withMessages([
                'media_asset_ids' => 'Uma das mídias da Biblioteca não corresponde ao tipo do plano.',
            ]);
        }

        if ($files->count() + $libraryMedia->count() > $plan->media_limit) {
            throw ValidationException::withMessages([
                'files' => "Este plano permite no máximo {$plan->media_limit} mídia(s).",
            ]);
        }

        if (count($data['display_point_ids']) > $plan->screen_limit) {
            throw ValidationException::withMessages([
                'display_point_ids' => "Este plano permite no máximo {$plan->screen_limit} ponto(s) de exibição.",
            ]);
        }

        $availableDisplayPoints = DisplayPoint::query()
            ->whereIn('id', $data['display_point_ids'])
            ->where('status', DisplayPoint::STATUS_ACTIVE)
            ->whereHas('establishment', fn ($query) => $query
                ->where('status', Establishment::STATUS_ACTIVE))
            ->count();

        if ($availableDisplayPoints !== count($data['display_point_ids'])) {
            throw ValidationException::withMessages([
                'display_point_ids' => 'Um ou mais pontos de exibição não estão disponíveis.',
            ]);
        }

        try {
            $result = DB::transaction(function () use ($data, $customerId, $files, $libraryMedia, $plan, $request, &$storedFiles): array {
                $subscription = CampaignSubscription::query()->create([
                    'user_id' => $customerId,
                    'plan_id' => $plan->id,
                    'status' => CampaignSubscription::STATUS_PENDING,
                    'price' => $plan->price,
                    'screen_limit' => $plan->screen_limit,
                    'media_limit' => $plan->media_limit,
                    'billing_cycle' => $plan->billing_cycle,
                    'media_type' => $plan->media_type,
                ]);

                $campaign = Campaign::query()->create([
                    'user_id' => $customerId,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'playback_mode' => $plan->media_limit > 1
                        ? $data['playback_mode']
                        : Campaign::PLAYBACK_SEQUENTIAL,
                    'status' => Campaign::STATUS_ACTIVE,
                ]);

                $campaign->categories()->sync($data['category_ids'] ?? []);
                $campaign->displayPoints()->sync($data['display_point_ids']);

                $uploadedMedia = $files->map(function ($file, int $index) use ($campaign, $customerId, $plan, $request, &$storedFiles): MediaAsset {
                    $fileData = $this->mediaFileService->store($file, $customerId);
                    $storedFiles[] = [
                        'disk' => $fileData['disk'],
                        'path' => $fileData['path'],
                    ];

                    if ($fileData['type'] !== $plan->media_type) {
                        throw ValidationException::withMessages([
                            'files' => 'Todos os arquivos devem corresponder ao tipo de mídia do plano selecionado.',
                        ]);
                    }

                    return MediaAsset::query()->create([
                        ...$fileData,
                        'user_id' => $customerId,
                        'uploaded_by' => $request->user()->id,
                        'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'description' => $campaign->description,
                        'processing_status' => MediaAsset::PROCESSING_READY,
                        'approval_status' => MediaAsset::APPROVAL_PENDING,
                    ]);
                });

                $mediaMap = $libraryMedia
                    ->mapWithKeys(fn (MediaAsset $media) => ["library:{$media->id}" => $media])
                    ->merge($uploadedMedia->mapWithKeys(
                        fn (MediaAsset $media, int $index) => ["file:{$index}" => $media],
                    ));
                $order = collect($data['media_order'] ?? [])
                    ->filter(fn (string $key) => $mediaMap->has($key))
                    ->concat($mediaMap->keys())
                    ->unique()
                    ->values();

                $campaign->mediaAssets()->sync($order->mapWithKeys(
                    fn (string $key, int $position) => [
                        $mediaMap->get($key)->id => ['position' => $position + 1],
                    ],
                ));

                $mediaMap->unique('id')->each(function (MediaAsset $media) use ($campaign, $data): void {
                    collect($data['display_point_ids'])->each(
                        fn (int $displayPointId) => MediaAssetDistribution::query()->create([
                            'campaign_id' => $campaign->id,
                            'media_asset_id' => $media->id,
                            'display_point_id' => $displayPointId,
                            'status' => MediaAssetDistribution::STATUS_PENDING,
                        ]),
                    );
                });

                $subscription->update(['campaign_id' => $campaign->id]);

                return compact('campaign', 'subscription');
            });

            return response()->json([
                'message' => 'Campanha enviada com sucesso. Sua contratação agora aguarda aprovação.',
                'campaign' => $result['campaign']->load(['categories', 'mediaAssets']),
                'subscription' => $result['subscription']->load('plan'),
            ], 201);
        } catch (Throwable $exception) {
            collect($storedFiles)->groupBy('disk')->each(
                fn ($items, string $disk) => Storage::disk($disk)->delete($items->pluck('path')->all()),
            );

            throw $exception;
        }
    }
}
