<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Models\MediaAsset;
use App\Domains\Setting\Services\StorageSettingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MediaFileService
{
    public function __construct(private readonly StorageSettingService $storageSettingService)
    {
    }

    public function store(mixed $file, int $customerId): array
    {
        $mimeType = (string) $file->getMimeType();
        $type = str_starts_with($mimeType, 'image/') ? MediaAsset::TYPE_IMAGE : MediaAsset::TYPE_VIDEO;
        $videoMetadata = $type === MediaAsset::TYPE_VIDEO ? $this->videoMetadata($file->getRealPath()) : null;
        $extension = strtolower($file->extension());
        $disk = $this->storageSettingService->mediaDisk();
        $path = $file->storeAs("media/{$customerId}", Str::uuid().'.'.$extension, $disk);

        if (! $path) {
            throw new RuntimeException('Não foi possível armazenar o arquivo da mídia.');
        }

        $metadata = [
            'type' => $type, 'original_name' => $file->getClientOriginalName(), 'disk' => $disk,
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
            $frameCount = isset($analysis['quicktime']['video']['frame_count'])
                ? (int) $analysis['quicktime']['video']['frame_count']
                : null;
            $frameRate = isset($analysis['video']['frame_rate'])
                ? (float) $analysis['video']['frame_rate']
                : null;
            $duration = $frameCount && $frameRate > 0 ? $frameCount / $frameRate : null;
        }

        if (! $duration || $duration <= 0) {
            $duration = $this->fragmentedMp4Duration($path);
        }

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

    /** Calcula a duração de MP4 fragmentado usando os boxes moof/traf. */
    private function fragmentedMp4Duration(string $path): ?float
    {
        $stream = fopen($path, 'rb');

        if (! $stream) {
            return null;
        }

        try {
            $fileSize = filesize($path);
            $topLevel = $this->mp4Boxes($stream, 0, $fileSize);
            $moov = collect($topLevel)->firstWhere('type', 'moov');

            if (! $moov) {
                return null;
            }

            $trackTimescales = $this->mp4TrackTimescales($stream, $moov);
            $defaultDurations = $this->mp4DefaultDurations($stream, $moov);
            $trackEnds = [];

            foreach (array_filter($topLevel, fn (array $box) => $box['type'] === 'moof') as $moofBox) {
                foreach ($this->mp4Boxes($stream, $moofBox['data'], $moofBox['end']) as $traf) {
                    if ($traf['type'] !== 'traf') {
                        continue;
                    }

                    $fragment = $this->mp4FragmentDuration($stream, $traf, $defaultDurations);

                    if (! $fragment) {
                        continue;
                    }

                    $trackEnds[$fragment['track_id']] = max(
                        $trackEnds[$fragment['track_id']] ?? 0,
                        $fragment['end'],
                    );
                }
            }

            $durations = collect($trackEnds)->map(
                fn (int|float $end, int $trackId) => isset($trackTimescales[$trackId]) && $trackTimescales[$trackId] > 0
                    ? $end / $trackTimescales[$trackId]
                    : null,
            )->filter();

            return $durations->isEmpty() ? null : (float) $durations->max();
        } finally {
            fclose($stream);
        }
    }

    private function mp4TrackTimescales(mixed $stream, array $moov): array
    {
        $timescales = [];

        foreach ($this->mp4Boxes($stream, $moov['data'], $moov['end']) as $trak) {
            if ($trak['type'] !== 'trak') {
                continue;
            }

            $children = $this->mp4Boxes($stream, $trak['data'], $trak['end']);
            $tkhd = collect($children)->firstWhere('type', 'tkhd');
            $mdia = collect($children)->firstWhere('type', 'mdia');

            if (! $tkhd || ! $mdia) {
                continue;
            }

            $tkhdData = $this->mp4Payload($stream, $tkhd, 32);
            $trackIdOffset = ord($tkhdData[0] ?? "\0") === 1 ? 20 : 12;
            $trackId = $this->mp4UInt32($tkhdData, $trackIdOffset);
            $mdhd = collect($this->mp4Boxes($stream, $mdia['data'], $mdia['end']))
                ->firstWhere('type', 'mdhd');

            if (! $trackId || ! $mdhd) {
                continue;
            }

            $mdhdData = $this->mp4Payload($stream, $mdhd, 32);
            $timescaleOffset = ord($mdhdData[0] ?? "\0") === 1 ? 20 : 12;
            $timescales[$trackId] = $this->mp4UInt32($mdhdData, $timescaleOffset);
        }

        return $timescales;
    }

    private function mp4DefaultDurations(mixed $stream, array $moov): array
    {
        $defaults = [];
        $mvex = collect($this->mp4Boxes($stream, $moov['data'], $moov['end']))
            ->firstWhere('type', 'mvex');

        if (! $mvex) {
            return $defaults;
        }

        foreach ($this->mp4Boxes($stream, $mvex['data'], $mvex['end']) as $trex) {
            if ($trex['type'] !== 'trex') {
                continue;
            }

            $data = $this->mp4Payload($stream, $trex, 20);
            $trackId = $this->mp4UInt32($data, 4);
            $defaults[$trackId] = $this->mp4UInt32($data, 12);
        }

        return $defaults;
    }

    private function mp4FragmentDuration(mixed $stream, array $traf, array $defaults): ?array
    {
        $children = $this->mp4Boxes($stream, $traf['data'], $traf['end']);
        $tfhd = collect($children)->firstWhere('type', 'tfhd');
        $tfdt = collect($children)->firstWhere('type', 'tfdt');

        if (! $tfhd || ! $tfdt) {
            return null;
        }

        $tfhdData = $this->mp4Payload($stream, $tfhd);
        $flags = $this->mp4Flags($tfhdData);
        $trackId = $this->mp4UInt32($tfhdData, 4);
        $cursor = 8;
        $cursor += ($flags & 0x000001) ? 8 : 0;
        $cursor += ($flags & 0x000002) ? 4 : 0;
        $defaultDuration = ($flags & 0x000008)
            ? $this->mp4UInt32($tfhdData, $cursor)
            : ($defaults[$trackId] ?? 0);
        $tfdtData = $this->mp4Payload($stream, $tfdt, 16);
        $baseTime = ord($tfdtData[0] ?? "\0") === 1
            ? $this->mp4UInt64($tfdtData, 4)
            : $this->mp4UInt32($tfdtData, 4);
        $fragmentDuration = 0;

        foreach ($children as $trun) {
            if ($trun['type'] !== 'trun') {
                continue;
            }

            $data = $this->mp4Payload($stream, $trun);
            $trunFlags = $this->mp4Flags($data);
            $sampleCount = $this->mp4UInt32($data, 4);
            $offset = 8;
            $offset += ($trunFlags & 0x000001) ? 4 : 0;
            $offset += ($trunFlags & 0x000004) ? 4 : 0;

            if (! ($trunFlags & 0x000100)) {
                $fragmentDuration += $sampleCount * $defaultDuration;
                continue;
            }

            for ($sample = 0; $sample < $sampleCount; $sample++) {
                $fragmentDuration += $this->mp4UInt32($data, $offset);
                $offset += 4;
                $offset += ($trunFlags & 0x000200) ? 4 : 0;
                $offset += ($trunFlags & 0x000400) ? 4 : 0;
                $offset += ($trunFlags & 0x000800) ? 4 : 0;
            }
        }

        return $trackId && $fragmentDuration > 0
            ? ['track_id' => $trackId, 'end' => $baseTime + $fragmentDuration]
            : null;
    }

    private function mp4Boxes(mixed $stream, int $start, int $end): array
    {
        $boxes = [];
        $offset = $start;

        while ($offset + 8 <= $end) {
            fseek($stream, $offset);
            $header = fread($stream, 8);

            if (strlen($header) !== 8) {
                break;
            }

            $size = $this->mp4UInt32($header, 0);
            $type = substr($header, 4, 4);
            $headerSize = 8;

            if ($size === 1) {
                $extended = fread($stream, 8);
                $size = $this->mp4UInt64($extended, 0);
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = $end - $offset;
            }

            if ($size < $headerSize || $offset + $size > $end) {
                break;
            }

            $boxes[] = [
                'type' => $type,
                'data' => $offset + $headerSize,
                'end' => $offset + $size,
                'size' => $size - $headerSize,
            ];
            $offset += $size;
        }

        return $boxes;
    }

    private function mp4Payload(mixed $stream, array $box, ?int $limit = null): string
    {
        fseek($stream, $box['data']);

        return fread($stream, $limit ? min($box['size'], $limit) : $box['size']);
    }

    private function mp4Flags(string $data): int
    {
        return (ord($data[1] ?? "\0") << 16)
            | (ord($data[2] ?? "\0") << 8)
            | ord($data[3] ?? "\0");
    }

    private function mp4UInt32(string $data, int $offset): int
    {
        return (int) (unpack('Nvalue', substr($data, $offset, 4))['value'] ?? 0);
    }

    private function mp4UInt64(string $data, int $offset): int|float
    {
        $parts = unpack('Nhigh/Nlow', substr($data, $offset, 8));

        return ($parts['high'] ?? 0) * 4294967296 + ($parts['low'] ?? 0);
    }
}
