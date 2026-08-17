<?php

namespace App\Domains\Media\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Models\MediaAssetDistribution;
use App\Domains\Media\Models\MediaAssetHistory;
use App\Domains\Media\Requests\MediaApprovalRequest;
use App\Domains\Media\Requests\MediaAssetRequest;
use App\Domains\Media\Services\MediaHistoryLogger;
use App\Domains\Media\Services\MediaStatusService;
use App\Domains\Setting\Services\StorageSettingService;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaAssetController extends Controller
{
    public function __construct(private readonly StorageSettingService $storageSettingService) {}

    /**
     * Lista as mídias com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'global' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', Rule::in([MediaAsset::TYPE_IMAGE, MediaAsset::TYPE_VIDEO])],
            'approval_status' => ['nullable', Rule::in([
                MediaAsset::APPROVAL_PENDING,
                MediaAsset::APPROVAL_APPROVED,
                MediaAsset::APPROVAL_REJECTED,
                MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = MediaAsset::query()->withCount([
            'campaigns',
            'campaigns as distributed_campaigns_count' => fn ($query) => $query
                ->where('campaigns.status', 'active')
                ->whereHas('subscription', fn ($query) => $query->where('status', 'active'))
                ->whereHas('displayPoints'),
        ])->with([
            'customer:id,name,last_name,email',
            'uploader:id,name,last_name',
            'approver:id,name,last_name',
        ]);

        if ($mediaId = $validated['media_id'] ?? null) {
            $query->whereKey($mediaId);
        }

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        foreach (['user_id', 'type', 'approval_status'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->where($field, $value);
            }
        }

        $media = $query->latest()->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json([
            'data' => $media->items(),
            'pagination' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    /**
     * Retorna os anunciantes disponíveis para cadastro e filtros.
     */
    public function customerOptions(): JsonResponse
    {
        $customers = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id', 'name', 'last_name', 'email', 'status']);

        return response()->json(['data' => $customers]);
    }

    /**
     * Retorna o histórico de alterações e aprovações da mídia.
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $media = MediaAsset::query()->find($id);

        if (! $media) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $history = MediaAssetHistory::query()
            ->with('user:id,name,last_name,email')
            ->where('media_asset_id', $media->id)
            ->latest()
            ->limit((int) ($validated['perPage'] ?? 3))
            ->get([
                'id', 'media_asset_id', 'user_id', 'event', 'description', 'old_values',
                'new_values', 'metadata', 'created_at',
            ]);

        $visibleFields = [
            'name', 'description', 'original_name', 'type', 'size_bytes',
            'width', 'height', 'duration_seconds', 'approval_status',
            'rejection_reason', 'user_id',
        ];

        $history->each(function (MediaAssetHistory $item) use ($visibleFields): void {
            $item->old_values = collect($item->old_values)->only($visibleFields)->all();
            $item->new_values = collect($item->new_values)->only($visibleFields)->all();
        });

        return response()->json(['data' => $history]);
    }

    /**
     * Armazena uma nova mídia no disco privado.
     */
    public function store(MediaAssetRequest $request): JsonResponse
    {
        $path = null;
        $disk = null;

        try {
            $data = $request->validated();
            $file = $request->file('file');
            unset($data['file']);

            $fileData = $this->storeFile($file, (int) $data['user_id']);
            $path = $fileData['path'];
            $disk = $fileData['disk'];
            $data = array_merge($data, $fileData, [
                'uploaded_by' => $request->user()->id,
                'processing_status' => MediaAsset::PROCESSING_READY,
                'approval_status' => MediaAsset::APPROVAL_PENDING,
            ]);

            $media = DB::transaction(fn () => MediaAsset::query()->create($data));
            $media->load(['customer:id,name,last_name,email', 'uploader:id,name,last_name']);

            MediaHistoryLogger::record(
                media: $media,
                event: 'created',
                description: "Mídia {$media->name} criada.",
                newValues: $media->toArray(),
            );

            AuditLogger::record(
                module: AuditLog::MODULE_MEDIA,
                action: AuditLog::ACTION_CREATED,
                description: "Mídia {$media->name} criada.",
                auditable: $media,
                newValues: $media->toArray(),
                request: $request,
                metadata: ['event' => 'created'],
            );

            return response()->json([
                'message' => 'Mídia criada com sucesso.',
                'media' => $media,
            ], 201);
        } catch (Throwable $exception) {
            if ($path && $disk) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Atualiza os dados e, opcionalmente, substitui o arquivo.
     */
    public function update(MediaAssetRequest $request, int $id): JsonResponse
    {
        $media = MediaAsset::query()->find($id);

        if (! $media) {
            return $this->notFound();
        }

        $data = $request->validated();
        $file = $request->file('file');
        unset($data['file']);

        if ((int) $data['user_id'] !== (int) $media->user_id && $media->campaigns()->exists()) {
            return response()->json([
                'message' => 'Não é possível trocar o cliente de uma mídia vinculada a campanhas.',
            ], 422);
        }

        $oldValues = $media->toArray();
        $oldPath = $media->path;
        $oldDisk = $media->disk;
        $newPath = null;
        $newDisk = null;

        try {
            if ($file) {
                $fileData = $this->storeFile($file, (int) $data['user_id']);
                $newPath = $fileData['path'];
                $newDisk = $fileData['disk'];
                $data = array_merge($data, $fileData, [
                    'processing_status' => MediaAsset::PROCESSING_READY,
                    'approval_status' => MediaAsset::APPROVAL_PENDING,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            DB::transaction(function () use ($media, $data, $file): void {
                $media->update($data);

                if ($file) {
                    $media->distributions()->update([
                        'status' => MediaAssetDistribution::STATUS_PENDING,
                        'processing_started_at' => null,
                        'distributed_at' => null,
                        'last_attempt_at' => null,
                        'error_message' => null,
                    ]);
                }
            });

            if ($newPath && $oldPath !== $newPath) {
                Storage::disk($oldDisk)->delete($oldPath);
            }

            $media->refresh()->load([
                'customer:id,name,last_name,email',
                'uploader:id,name,last_name',
                'approver:id,name,last_name',
            ]);

            MediaHistoryLogger::record(
                media: $media,
                event: $file ? 'file_replaced' : 'details_updated',
                description: $file
                    ? "Arquivo da mídia {$media->name} substituído."
                    : "Dados da mídia {$media->name} alterados.",
                oldValues: $oldValues,
                newValues: $media->toArray(),
            );

            AuditLogger::record(
                module: AuditLog::MODULE_MEDIA,
                action: AuditLog::ACTION_UPDATED,
                description: "Mídia {$media->name} atualizada.",
                auditable: $media,
                oldValues: $oldValues,
                newValues: $media->toArray(),
                request: $request,
                metadata: ['event' => $file ? 'file_replaced' : 'details_updated'],
            );

            return response()->json([
                'message' => 'Mídia atualizada com sucesso.',
                'media' => $media,
            ]);
        } catch (Throwable $exception) {
            if ($newPath && $newDisk) {
                Storage::disk($newDisk)->delete($newPath);
            }

            throw $exception;
        }
    }

    /**
     * Aprova, rejeita ou arquiva uma mídia após análise interna.
     */
    public function updateApproval(MediaApprovalRequest $request, int $id): JsonResponse
    {
        $media = MediaAsset::query()->find($id);

        if (! $media) {
            return $this->notFound();
        }

        if ($media->processing_status !== MediaAsset::PROCESSING_READY) {
            return response()->json([
                'message' => 'A mídia precisa concluir o processamento antes da análise.',
            ], 422);
        }

        $data = $request->validated();
        $oldValues = $media->toArray();
        $isApproved = $data['approval_status'] === MediaAsset::APPROVAL_APPROVED;

        if ($isApproved) {
            MediaStatusService::markAsReviewed($media, $request->user()->id);
        } else {
            $media->update([
                'approval_status' => MediaAsset::APPROVAL_REJECTED,
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        $media->load([
            'customer:id,name,last_name,email',
            'uploader:id,name,last_name',
            'approver:id,name,last_name',
        ]);

        MediaHistoryLogger::record(
            media: $media,
            event: $isApproved ? 'approved' : 'rejected',
            description: $isApproved
                ? "Mídia {$media->name} aprovada."
                : "Mídia {$media->name} rejeitada.",
            oldValues: $oldValues,
            newValues: $media->toArray(),
            metadata: ['decision' => $data['approval_status']],
        );

        AuditLogger::record(
            module: AuditLog::MODULE_MEDIA,
            action: AuditLog::ACTION_UPDATED,
            description: "Situação de aprovação da mídia {$media->name} alterada.",
            auditable: $media,
            oldValues: $oldValues,
            newValues: $media->toArray(),
            request: $request,
            metadata: [
                'event' => $isApproved ? 'approved' : 'rejected',
                'decision' => $data['approval_status'],
            ],
        );

        return response()->json([
            'message' => 'Situação da mídia atualizada com sucesso.',
            'media' => $media,
        ]);
    }

    /**
     * Entrega o arquivo somente para usuários autenticados e autorizados.
     */
    public function content(int $id): StreamedResponse|Response|JsonResponse
    {
        $media = MediaAsset::query()->find($id);

        if (! $media) {
            return $this->notFound();
        }

        if (! Storage::disk($media->disk)->exists($media->path)) {
            return response()->json(['message' => 'Arquivo da mídia não encontrado.'], 404);
        }

        return Storage::disk($media->disk)->response(
            $media->path,
            $media->original_name,
            ['Content-Disposition' => 'inline'],
        );
    }

    /**
     * Exclui a mídia e seu arquivo privado.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = MediaAsset::query()->find($id);

        if (! $media) {
            return $this->notFound();
        }

        if ($media->campaigns()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir uma mídia vinculada a campanhas.',
            ], 422);
        }

        $oldValues = $media->toArray();

        AuditLogger::record(
            module: AuditLog::MODULE_MEDIA,
            action: AuditLog::ACTION_DELETED,
            description: "Mídia {$media->name} excluída.",
            auditable: $media,
            oldValues: $oldValues,
        );

        $media->delete();
        Storage::disk($media->disk)->delete($media->path);

        return response()->json(['message' => 'Mídia excluída com sucesso.']);
    }

    /**
     * Armazena o arquivo e retorna seus metadados confiáveis.
     *
     * @return array<string, mixed>
     */
    private function storeFile(mixed $file, int $customerId): array
    {
        $mimeType = (string) $file->getMimeType();
        $type = str_starts_with($mimeType, 'image/') ? MediaAsset::TYPE_IMAGE : MediaAsset::TYPE_VIDEO;
        $videoMetadata = $type === MediaAsset::TYPE_VIDEO
            ? $this->extractVideoMetadata($file->getRealPath())
            : null;
        $extension = strtolower($file->extension());
        $filename = Str::uuid().'.'.$extension;
        $disk = $this->storageSettingService->mediaDisk();
        $path = $file->storeAs("media/{$customerId}", $filename, $disk);

        if (! $path) {
            throw new RuntimeException('Não foi possível armazenar o arquivo da mídia.');
        }

        $metadata = [
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => $file->getSize(),
            'width' => null,
            'height' => null,
            'duration_seconds' => null,
            'orientation' => null,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'processing_error' => null,
        ];

        if ($type === MediaAsset::TYPE_IMAGE) {
            $dimensions = getimagesize($file->getRealPath());

            if ($dimensions) {
                $metadata['width'] = $dimensions[0];
                $metadata['height'] = $dimensions[1];
                $metadata['orientation'] = match (true) {
                    $dimensions[0] > $dimensions[1] => 'landscape',
                    $dimensions[0] < $dimensions[1] => 'portrait',
                    default => 'square',
                };
            }
        } else {
            $metadata = array_merge($metadata, $videoMetadata);
        }

        return $metadata;
    }

    /**
     * Extrai os metadados do vídeo e impede arquivos maiores que 15 segundos.
     *
     * @return array{duration_seconds: int, width: int|null, height: int|null, orientation: string|null}
     */
    private function extractVideoMetadata(string $path): array
    {
        $analysis = (new \getID3)->analyze($path);
        $duration = isset($analysis['playtime_seconds'])
            ? (float) $analysis['playtime_seconds']
            : null;

        if (! $duration || $duration <= 0) {
            throw ValidationException::withMessages([
                'file' => 'Não foi possível identificar a duração do vídeo.',
            ]);
        }

        if ($duration > 15) {
            throw ValidationException::withMessages([
                'file' => 'O vídeo deve possuir no máximo 15 segundos.',
            ]);
        }

        $width = isset($analysis['video']['resolution_x'])
            ? (int) $analysis['video']['resolution_x']
            : null;
        $height = isset($analysis['video']['resolution_y'])
            ? (int) $analysis['video']['resolution_y']
            : null;

        return [
            'duration_seconds' => (int) ceil($duration),
            'width' => $width,
            'height' => $height,
            'orientation' => match (true) {
                ! $width || ! $height => null,
                $width > $height => 'landscape',
                $width < $height => 'portrait',
                default => 'square',
            },
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Mídia não encontrada.'], 404);
    }
}
