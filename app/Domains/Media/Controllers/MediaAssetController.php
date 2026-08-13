<?php

namespace App\Domains\Media\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Requests\MediaApprovalRequest;
use App\Domains\Media\Requests\MediaAssetRequest;
use App\Domains\Media\Services\MediaStatusService;
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
    /**
     * Lista as mídias com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
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

        $query = MediaAsset::query()->with([
            'customer:id,name,last_name,email',
            'uploader:id,name,last_name',
            'approver:id,name,last_name',
        ]);

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
     * Armazena uma nova mídia no disco privado.
     */
    public function store(MediaAssetRequest $request): JsonResponse
    {
        $path = null;

        try {
            $data = $request->validated();
            $file = $request->file('file');
            unset($data['file']);

            $fileData = $this->storeFile($file, (int) $data['user_id']);
            $path = $fileData['path'];
            $data = array_merge($data, $fileData, [
                'uploaded_by' => $request->user()->id,
                'processing_status' => MediaAsset::PROCESSING_READY,
                'approval_status' => MediaAsset::APPROVAL_PENDING,
            ]);

            $media = DB::transaction(fn () => MediaAsset::query()->create($data));
            $media->load(['customer:id,name,last_name,email', 'uploader:id,name,last_name']);

            AuditLogger::record(
                module: AuditLog::MODULE_MEDIA,
                action: AuditLog::ACTION_CREATED,
                description: "Mídia {$media->name} criada.",
                auditable: $media,
                newValues: $media->toArray(),
                request: $request,
            );

            return response()->json([
                'message' => 'Mídia criada com sucesso.',
                'media' => $media,
            ], 201);
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
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
        $newPath = null;

        try {
            if ($file) {
                $fileData = $this->storeFile($file, (int) $data['user_id']);
                $newPath = $fileData['path'];
                $data = array_merge($data, $fileData, [
                    'processing_status' => MediaAsset::PROCESSING_READY,
                    'approval_status' => MediaAsset::APPROVAL_PENDING,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            DB::transaction(fn () => $media->update($data));

            if ($newPath && $oldPath !== $newPath) {
                Storage::disk($media->disk)->delete($oldPath);
            }

            $media->refresh()->load([
                'customer:id,name,last_name,email',
                'uploader:id,name,last_name',
                'approver:id,name,last_name',
            ]);

            AuditLogger::record(
                module: AuditLog::MODULE_MEDIA,
                action: AuditLog::ACTION_UPDATED,
                description: "Mídia {$media->name} atualizada.",
                auditable: $media,
                oldValues: $oldValues,
                newValues: $media->toArray(),
                request: $request,
            );

            return response()->json([
                'message' => 'Mídia atualizada com sucesso.',
                'media' => $media,
            ]);
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
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

        AuditLogger::record(
            module: AuditLog::MODULE_MEDIA,
            action: AuditLog::ACTION_UPDATED,
            description: "Situação de aprovação da mídia {$media->name} alterada.",
            auditable: $media,
            oldValues: $oldValues,
            newValues: $media->toArray(),
            request: $request,
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
        $path = $file->storeAs("media/{$customerId}", $filename, 'local');

        if (! $path) {
            throw new RuntimeException('Não foi possível armazenar o arquivo da mídia.');
        }

        $metadata = [
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
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
