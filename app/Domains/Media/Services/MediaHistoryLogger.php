<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Models\MediaAsset;
use App\Domains\Media\Models\MediaAssetHistory;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Auth;

class MediaHistoryLogger
{
    public static function record(
        MediaAsset $media,
        string $event,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $user = null,
    ): MediaAssetHistory {
        $user ??= Auth::user();

        return MediaAssetHistory::query()->create([
            'media_asset_id' => $media->id,
            'user_id' => $user?->id,
            'event' => $event,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);
    }
}
