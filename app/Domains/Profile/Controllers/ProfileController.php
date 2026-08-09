<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Appearance\Models\UserAppearanceSetting;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Profile\Requests\AppearanceSettingsRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domains\User\Models\User;

class ProfileController extends Controller
{
    /**
     * Atualiza as preferências visuais do usuário autenticado.
     */
    public function updateAppearance(AppearanceSettingsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $oldValues = ['appearance_settings' => $user->appearance_settings];

        $appearanceSetting = $user->appearanceSetting()
            ->updateOrCreate(
                ['user_id' => $user->id],
                $request->validated(),
            );

        $user->setRelation('appearanceSetting', $appearanceSetting);

        AuditLogger::record(
            module: AuditLog::MODULE_PROFILE,
            action: AuditLog::ACTION_UPDATED,
            description: 'Preferências de aparência atualizadas.',
            auditable: $user,
            oldValues: $oldValues,
            newValues: ['appearance_settings' => $appearanceSetting->toSettingsArray()],
            user: $user,
            request: $request,
        );

        return response()->json([
            'message' => 'Aparência atualizada com sucesso.',
            'appearance_settings' => $appearanceSetting->toSettingsArray(),
        ]);
    }

    /**
     * Restaura as preferências visuais padrão do sistema.
     */
    public function resetAppearance(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $oldValues = ['appearance_settings' => $user->appearance_settings];

        $appearanceSetting = $user->appearanceSetting()
            ->updateOrCreate(
                ['user_id' => $user->id],
                UserAppearanceSetting::defaults(),
            );

        $user->setRelation('appearanceSetting', $appearanceSetting);

        AuditLogger::record(
            module: AuditLog::MODULE_PROFILE,
            action: AuditLog::ACTION_UPDATED,
            description: 'Preferências de aparência restauradas para o padrão.',
            auditable: $user,
            oldValues: $oldValues,
            newValues: ['appearance_settings' => $appearanceSetting->toSettingsArray()],
            user: $user,
            request: $request,
        );

        return response()->json([
            'message' => 'Aparência restaurada para o padrão.',
            'appearance_settings' => $appearanceSetting->toSettingsArray(),
        ]);
    }
}