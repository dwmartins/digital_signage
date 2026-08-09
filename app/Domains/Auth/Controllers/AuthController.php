<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Valida se o token Sanctum é válido e retorna o usuário autenticado.
     * @param Request $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || ! $user->isActive()) {
            $this->forceLogout($request);

            return response()->json([
                'message' => 'Sessão inválida.',
                'is_valid' => false,
                'force_logout' => true,
            ], 401);
        }

        return response()->json([
            'message' => 'Usuário autenticado.',
            'is_valid' => true,
            'user' => $user,
            'auth' => $this->authPayload($user, $request),
        ]);
    }

    /**
     * Autentica o usuário usando a sessão do Laravel/Sanctum SPA.
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        $request->session()->regenerate();
        $user->updateLastLogin();

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => $user->fresh(),
            'auth' => $this->authPayload($user, $request),
        ]);
    }

    /**
     * Força o logout
     * @param Request $request
     * @return void
     */
    public function forceLogout(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();

        // Revoga tokens (mobile / API)
        if ($user && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    /**
     * Encerra a sessão autenticada.
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        AuditLogger::record(
            module: AuditLog::MODULE_AUTH,
            action: AuditLog::ACTION_LOGOUT,
            description: 'Logout realizado.',
            auditable: $user,
            user: $user,
            request: $request,
        );

        $this->forceLogout($request);

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Monta os dados de autorização e contexto operacional do usuário.
     *
     * @return array<string, mixed>
     */
    private function authPayload(User $user, Request $request): array
    {
        return [
            ...$user->authContext(),
        ];
    }
}
