<?php

namespace App\Domains\Setting\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Setting\Models\StorageSetting;
use App\Domains\Setting\Requests\UpdateStorageSettingRequest;
use App\Domains\Setting\Services\StorageSettingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StorageSettingController extends Controller
{
    public function __construct(private readonly StorageSettingService $storageSettingService)
    {
    }

    /** Retorna os provedores sem expor suas chaves secretas. */
    public function show(): JsonResponse
    {
        return response()->json([
            'setting' => $this->responseData($this->setting()),
        ]);
    }

    /** Atualiza o destino dos próximos arquivos enviados. */
    public function update(UpdateStorageSettingRequest $request): JsonResponse
    {
        $setting = $this->setting();
        $data = $request->safe()->except([
            'r2_secret_access_key',
            'aws_secret_access_key',
        ]);

        foreach (['r2_secret_access_key', 'aws_secret_access_key'] as $secretField) {
            if ($request->filled($secretField)) {
                $data[$secretField] = $request->validated($secretField);
            }
        }

        $this->validateSelectedSecret($request->validated('driver'), $setting, $data);

        $visibleFields = [
            'driver', 'r2_account_id', 'r2_access_key_id', 'r2_bucket', 'r2_endpoint',
            'aws_access_key_id', 'aws_region', 'aws_bucket', 'aws_endpoint',
            'aws_url', 'aws_use_path_style_endpoint',
        ];
        $oldValues = $setting->only($visibleFields);
        $data['updated_by'] = $request->user()->id;
        $setting->update($data);
        $setting->refresh();
        $this->storageSettingService->apply($setting);

        AuditLogger::record(
            module: AuditLog::MODULE_SETTINGS,
            action: AuditLog::ACTION_UPDATED,
            description: 'Configuração de armazenamento de mídias atualizada.',
            auditable: $setting,
            oldValues: $oldValues,
            newValues: $setting->only($visibleFields),
            request: $request,
        );

        return response()->json([
            'message' => 'Configuração de armazenamento salva com sucesso.',
            'setting' => $this->responseData($setting),
        ]);
    }

    private function setting(): StorageSetting
    {
        return StorageSetting::query()->firstOrCreate(['key' => 'media'], [
            'driver' => StorageSetting::DRIVER_LOCAL,
            'aws_use_path_style_endpoint' => false,
        ]);
    }

    private function validateSelectedSecret(string $driver, StorageSetting $setting, array $data): void
    {
        $field = match ($driver) {
            StorageSetting::DRIVER_R2 => 'r2_secret_access_key',
            StorageSetting::DRIVER_S3 => 'aws_secret_access_key',
            default => null,
        };

        if ($field && empty($data[$field]) && empty($setting->{$field})) {
            throw ValidationException::withMessages([
                $field => 'Informe a chave secreta do provedor selecionado.',
            ]);
        }
    }

    private function responseData(StorageSetting $setting): array
    {
        return [
            ...$setting->only([
                'id', 'driver', 'r2_account_id', 'r2_access_key_id',
                'r2_bucket', 'r2_endpoint', 'aws_access_key_id', 'aws_region',
                'aws_bucket', 'aws_endpoint', 'aws_url',
                'aws_use_path_style_endpoint', 'updated_at',
            ]),
            'r2_secret_configured' => filled($setting->r2_secret_access_key),
            'aws_secret_configured' => filled($setting->aws_secret_access_key),
        ];
    }
}
