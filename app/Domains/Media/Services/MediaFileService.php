<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Models\MediaAsset;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MediaFileService
{
    public function store(mixed $file, int $customerId): array
    {
        $mimeType = (string) $file->getMimeType();
        $type = str_starts_with($mimeType, 'image/') ? MediaAsset::TYPE_IMAGE : MediaAsset::TYPE_VIDEO;
        $videoMetadata = $type === MediaAsset::TYPE_VIDEO ? $this->videoMetadata($file->getRealPath()) : null;
        $extension = strtolower($file->extension());
        $path = $file->storeAs("media/{$customerId}", Str::uuid().'.'.$extension, 'local');

        if (! $path) {
            throw new RuntimeException('Não foi possível armazenar o arquivo da mídia.');
        }

        $metadata = [
            'type' => $type, 'original_name' => $file->getClientOriginalName(), 'disk' => 'local',
            'path' => $path, 'mime_type' => $mimeType, 'extension' => $extension,
            'size_bytes' => $file->getSize(), 'width' => null, 'height' => null,
            'duration_seconds' => null, 'orientation' => null,
            'checksum' => hash_file('sha256', $file->getRealPath()), 'processing_error' => null,
        ];

        if ($type === MediaAsset::TYPE_IMAGE) {
            $dimensions = getimagesize($file->getRealPath());
            if ($dimensions) {
                $metadata['width'] = $dimensions[0];
                $metadata['height'] = $dimensions[1];
                $metadata['orientation'] = $dimensions[0] === $dimensions[1] ? 'square' : ($dimensions[0] > $dimensions[1] ? 'landscape' : 'portrait');
            }
        } else {
            $metadata = array_merge($metadata, $videoMetadata);
        }

        return $metadata;
    }

    private function videoMetadata(string $path): array
    {
        $analysis = (new \getID3)->analyze($path);
        $duration = isset($analysis['playtime_seconds']) ? (float) $analysis['playtime_seconds'] : null;
        if (! $duration || $duration <= 0) {
            throw ValidationException::withMessages(['files' => 'Não foi possível identificar a duração de um dos vídeos.']);
        }
        if ($duration > 15) {
            throw ValidationException::withMessages(['files' => 'Todos os vídeos devem possuir no máximo 15 segundos.']);
        }
        $width = isset($analysis['video']['resolution_x']) ? (int) $analysis['video']['resolution_x'] : null;
        $height = isset($analysis['video']['resolution_y']) ? (int) $analysis['video']['resolution_y'] : null;

        return ['duration_seconds' => (int) ceil($duration), 'width' => $width, 'height' => $height,
            'orientation' => ! $width || ! $height ? null : ($width === $height ? 'square' : ($width > $height ? 'landscape' : 'portrait'))];
    }
}
