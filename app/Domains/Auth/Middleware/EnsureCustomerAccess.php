<?php

namespace App\Domains\Auth\Middleware;

use App\Domains\User\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAccess
{
    /** Valida se o usuário autenticado pertence à área de anunciantes. */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return $this->deny('Usuário não autenticado.', Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->isCustomer()) {
            return $this->deny('Esta área é exclusiva para anunciantes.');
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
