<?php

namespace App\Domains\User\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\User\Models\User;
use App\Domains\User\Requests\SupportUserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SupportUserController extends Controller
{
    /**
     * Obtenha uma lista paginada de usuários suporte com filtros opcionais.
     *
     * Filtros disponíveis:
     * - global: termo de pesquisa aplicado aos campos de nome, sobrenome e e-mail.
     * - status: filtrar por status (active, inactive ou blocked).
     *
     * Paginação:
     * - perPage: quantidade de itens por página (padrão: 7).
     * - page: número da página (padrão: 1);
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE,
                User::STATUS_BLOCKED,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['perPage'] ?? 7);
        $query = User::query()->where('role', User::ROLE_SUPPORT);

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        $users = $query
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Cria um usuário suporte na plataforma.
     *
     * @param SupportUserRequest $request
     * @return JsonResponse
     */
    public function store(SupportUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = User::ROLE_SUPPORT;

        /** @var User $user */
        $user = User::create($data);

        AuditLogger::record(
            module: AuditLog::MODULE_SUPPORT_USERS,
            action: AuditLog::ACTION_CREATED,
            description: "Usuário suporte {$user->full_name} criado.",
            auditable: $user,
            newValues: $user->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Usuário suporte criado com sucesso.',
            'user' => $user
        ], 201);
    }

    /**
     * Atualiza os dados cadastrais de um usuário suporte.
     *
     * @param SupportUserRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(SupportUserRequest $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()
            ->where('role', User::ROLE_SUPPORT)
            ->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Usuário suporte não encontrado.',
            ], 404);
        }

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $auditFields = array_diff(array_keys($data), ['password']);
        $oldValues = $user->only($auditFields);

        $user->update($data);

        AuditLogger::record(
            module: AuditLog::MODULE_SUPPORT_USERS,
            action: AuditLog::ACTION_UPDATED,
            description: "Usuário suporte {$user->full_name} atualizado.",
            auditable: $user,
            oldValues: $oldValues,
            newValues: $user->only($auditFields),
            request: $request,
        );

        return response()->json([
            'message' => 'Usuário suporte atualizado com sucesso.',
            'user' => $user,
        ]);
    }
}