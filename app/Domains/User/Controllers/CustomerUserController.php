<?php

namespace App\Domains\User\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Permission\Models\Permission;
use App\Domains\User\Models\User;
use App\Domains\User\Requests\CustomerUserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerUserController extends Controller
{
    /**
     * Lista os clientes anunciantes com filtros e paginação.
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
        $query = User::query()->where('role', User::ROLE_CUSTOMER);

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

        $users = $query->latest()->paginate($perPage);

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
     * Cadastra um novo cliente anunciante.
     */
    public function store(CustomerUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = User::ROLE_CUSTOMER;

        if (! $request->user()->hasPermission(Permission::CUSTOMERS_AUDIT_UPDATE)) {
            $data['audit_logs_enabled'] = true;
        }

        /** @var User $user */
        $user = User::query()->create($data);

        AuditLogger::record(
            module: AuditLog::MODULE_CUSTOMERS,
            action: AuditLog::ACTION_CREATED,
            description: "Cliente anunciante {$user->full_name} criado.",
            auditable: $user,
            newValues: $user->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Cliente anunciante criado com sucesso.',
            'user' => $user,
        ], 201);
    }

    /**
     * Atualiza os dados de um cliente anunciante.
     */
    public function update(CustomerUserRequest $request, int $id): JsonResponse
    {
        $user = $this->findCustomer($id);

        if (! $user) {
            return $this->notFound();
        }

        $data = $request->validated();

        if (! $request->user()->hasPermission(Permission::CUSTOMERS_AUDIT_UPDATE)) {
            unset($data['audit_logs_enabled']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $auditFields = array_diff(array_keys($data), ['password']);
        $oldValues = $user->only($auditFields);

        $user->update($data);

        AuditLogger::record(
            module: AuditLog::MODULE_CUSTOMERS,
            action: AuditLog::ACTION_UPDATED,
            description: "Cliente anunciante {$user->full_name} atualizado.",
            auditable: $user,
            oldValues: $oldValues,
            newValues: $user->only($auditFields),
            request: $request,
        );

        return response()->json([
            'message' => 'Cliente anunciante atualizado com sucesso.',
            'user' => $user,
        ]);
    }

    /**
     * Exclui um cliente anunciante.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->findCustomer($id);

        if (! $user) {
            return $this->notFound();
        }

        AuditLogger::record(
            module: AuditLog::MODULE_CUSTOMERS,
            action: AuditLog::ACTION_DELETED,
            description: "Cliente anunciante {$user->full_name} excluído.",
            auditable: $user,
            oldValues: $user->toArray(),
        );

        $user->delete();

        return response()->json([
            'message' => 'Cliente anunciante excluído com sucesso.',
        ]);
    }

    /**
     * Localiza um usuário com perfil de cliente.
     */
    private function findCustomer(int $id): ?User
    {
        return User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->find($id);
    }

    /**
     * Retorna a resposta padrão para cliente não encontrado.
     */
    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Cliente anunciante não encontrado.',
        ], 404);
    }
}
