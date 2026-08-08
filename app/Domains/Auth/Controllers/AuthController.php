<?php

namespace App\Domains\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\User\Models\User;

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

        if(!$user || !$user->isActive()) {
            $this->forceLogout($request);

            return response()->json([
                'message'      => 'Sessão inválida.',
                'is_valid'     => false,
                'force_logout' => true
            ], 401);
        }

        return response()->json([
            'message'  => 'Usuário autenticado.',
            'is_valid' => true,
            'user'     => $user,
            'auth'     => $this->authPayload($user, $request),
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
