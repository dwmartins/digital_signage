<?php

namespace App\Domains\Setting\Services;

use App\Domains\Setting\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;

class StorageSettingService
{
    public const DISK_R2 = 'media_r2';

    public const DISK_S3 = 'media_s3';

    private ?StorageSetting $setting = null;

    /** Aplica todos os provedores configurados para manter arquivos antigos acessíveis. */
    public function apply(?StorageSetting $setting): void
    {
        $this->setting = $setting;

        if (! $setting) {
            return;
        }

        config([
            'filesystems.disks.'.self::DISK_R2 => [
                'driver' => 's3',
                'key' => $setting->r2_access_key_id,
                'secret' => $setting->r2_secret_access_key,
                'region' => 'auto',
                'bucket' => $setting->r2_bucket,
                'url' => null,
                'endpoint' => $setting->r2_endpoint ?: $this->r2Endpoint($setting->r2_account_id),
                'use_path_style_endpoint' => false,
                'throw' => false,
                'report' => false,
            ],
            'filesystems.disks.'.self::DISK_S3 => [
                'driver' => 's3',
                'key' => $setting->aws_access_key_id,
                'secret' => $setting->aws_secret_access_key,
                'region' => $setting->aws_region,
                'bucket' => $setting->aws_bucket,
                'url' => $setting->aws_url,
                'endpoint' => $setting->aws_endpoint,
                'use_path_style_endpoint' => $setting->aws_use_path_style_endpoint,
                'throw' => false,
                'report' => false,
            ],
        ]);

        Storage::forgetDisk([self::DISK_R2, self::DISK_S3]);
    }

    /** Retorna o disco onde os próximos arquivos de mídia serão gravados. */
    public function mediaDisk(): string
    {
        $driver = $this->setting?->driver
            ?? StorageSetting::query()->where('key', 'media')->value('driver')
            ?? StorageSetting::DRIVER_LOCAL;

        return match ($driver) {
            StorageSetting::DRIVER_R2 => self::DISK_R2,
            StorageSetting::DRIVER_S3 => self::DISK_S3,
            default => StorageSetting::DRIVER_LOCAL,
        };
    }

    private function r2Endpoint(?string $accountId): ?string
    {
        return $accountId ? "https://{$accountId}.r2.cloudflarestorage.com" : null;
    }
}
