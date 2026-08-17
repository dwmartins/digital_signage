<?php

namespace App\Domains\Setting\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Setting\Models\EmailSetting;
use App\Domains\Setting\Requests\UpdateEmailSettingRequest;
use App\Domains\Setting\Services\EmailSettingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class EmailSettingController extends Controller
{
    public function __construct(private readonly EmailSettingService $emailSettingService)
    {
    }

    /** Retorna a configuração de envio sem expor a senha armazenada. */
    public function show(): JsonResponse
    {
        return response()->json([
            'setting' => $this->responseData($this->setting()),
        ]);
    }

    /** Atualiza e aplica imediatamente a configuração de e-mail. */
    public function update(UpdateEmailSettingRequest $request): JsonResponse
    {
        $setting = $this->setting();
        $oldValues = $setting->only([
            'enabled', 'host', 'port', 'encryption', 'username',
            'from_address', 'from_name', 'timeout',
        ]);
        $data = $request->safe()->except('password');

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $data['updated_by'] = $request->user()->id;
        $setting->update($data);
        $this->emailSettingService->apply($setting->fresh());

        AuditLogger::record(
            module: AuditLog::MODULE_SETTINGS,
            action: AuditLog::ACTION_UPDATED,
            description: 'Configuração de envio de e-mail atualizada.',
            auditable: $setting,
            oldValues: $oldValues,
            newValues: $setting->only(array_keys($oldValues)),
            request: $request,
        );

        return response()->json([
            'message' => 'Configuração de e-mail salva com sucesso.',
            'setting' => $this->responseData($setting->fresh()),
        ]);
    }

    private function setting(): EmailSetting
    {
        return EmailSetting::query()->firstOrCreate(['key' => 'smtp'], [
            'enabled' => false,
            'host' => '127.0.0.1',
            'port' => 587,
            'encryption' => EmailSetting::ENCRYPTION_TLS,
            'from_address' => 'noreply@example.com',
            'from_name' => config('app.name'),
            'timeout' => 30,
        ]);
    }

    private function responseData(EmailSetting $setting): array
    {
        return [
            ...$setting->only([
                'id', 'enabled', 'host', 'port', 'encryption', 'username',
                'from_address', 'from_name', 'timeout', 'updated_at',
            ]),
            'password_configured' => filled($setting->password),
        ];
    }
}
