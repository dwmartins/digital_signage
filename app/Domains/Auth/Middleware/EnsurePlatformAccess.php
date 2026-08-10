<?php

namespace App\Domains\Auth\Middleware;

use App\Domains\Permission\Models\Permission;
use App\Domains\User\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAccess
{
    /**
     * Valida se o usuário pode acessar uma rota da plataforma.
     *
     * Usuários internos da plataforma precisam possuir a permissão informada.
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user) {
            return $this->deny('Usuário não autenticado.', Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isPlatformUser()) {
            return $this->deny('Acesso restrito aos usuários da plataforma.');
        }

        if (!$user->hasPermission($permission)) {
            return $this->deny('Você não possui permissão para acessar este recurso.');
        }

        return $next($request);
    }

    private function deny(string $message, int $status = Response::HTTP_FORBIDDEN): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
