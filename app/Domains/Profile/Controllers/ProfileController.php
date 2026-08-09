<?php

namespace App\Domains\Profile\Controllers;

use App\Domains\Appearance\Models\UserAppearanceSetting;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Profile\Requests\AppearanceSettingsRequest;
use App\Domains\Profile\Requests\ProfileRequest;
use App\Domains\Profile\Requests\UpdatePasswordRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Atualiza os dados básicos do usuário autenticado.
     * 
     * @param ProfileRequest $request
     * @return JsonResponse
     */
    public function update(ProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $oldValues = $user->only(array_keys($request->validated()));
        
        $user->update($request->validated());

        AuditLogger::record(
            module: AuditLog::MODULE_PROFILE,
            action: AuditLog::ACTION_UPDATED,
            description: 'Perfil atualizado.',
            auditable: $user,
            oldValues: $oldValues,
            newValues: $user->only(array_keys($request->validated())),
            user: $user,
            request: $request,
        );

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user'    => $user
        ]);
    }

    /**
     * Atualiza a senha do usuário autenticado.
     * 
     * @param UpdatePasswordRequest $request
     * @return JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $forcedLogout = $request->boolean('force_logout');

        if(!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'A senha atual informada está incorreta.',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        AuditLogger::record(
            module: AuditLog::MODULE_PROFILE,
            action: AuditLog::ACTION_PASSWORD_UPDATED,
            description: 'Senha atualizada.',
            auditable: $user,
            metadata: ['force_logout' => $forcedLogout],
            user: $user,
            request: $request,
        );

        if($forcedLogout) {
            $this->logoutOtherDevices($user);
        }

        return response()->json([
            'message' => 'Senha atualizada com sucesso.',
        ]);
    }

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

    /**
     * Retorna todas as sessões do usuário
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sessions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn($session) => [
                'id'            => $session->id,
                'ip_address'    => $session->ip_address,
                'user_agent'    => $session->user_agent,
                'last_activity' => $session->last_activity,
                'is_current'    => $session->id === request()->session()->getId(),
            ]);

        return response()->json($sessions);
    }

    /**
     * Exclui uma sessão especifica.
     * 
     * @param Request $request
     * @param string $id da sessão
     */
    public function removeSession(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->delete();
        
        return response()->json([
            'message' => 'Sessão removida com sucesso.'
        ]);
    }

    /**
     * Força o logout dos demais dispositivos.
     * 
     * @return void
     */
    private function logoutOtherDevices(User $user): void
    {
        $currentSessionId = session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}