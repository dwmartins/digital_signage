<?php

namespace App\Domains\Establishment\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Establishment\Models\Establishment;
use App\Domains\Establishment\Requests\EstablishmentRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstablishmentController extends Controller
{
    /**
     * Lista os estabelecimentos com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                Establishment::STATUS_ACTIVE,
                Establishment::STATUS_INACTIVE,
                Establishment::STATUS_BLOCKED,
            ])],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Establishment::query()->with([
            'city.state:id,name,code',
            'neighborhood:id,city_id,name',
        ]);

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'city_id', 'neighborhood_id'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->where($field, $value);
            }
        }

        if ($stateId = $validated['state_id'] ?? null) {
            $query->whereHas('city', fn ($query) => $query->where('state_id', $stateId));
        }

        $establishments = $query
            ->latest()
            ->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json([
            'data' => $establishments->items(),
            'pagination' => [
                'current_page' => $establishments->currentPage(),
                'last_page' => $establishments->lastPage(),
                'per_page' => $establishments->perPage(),
                'total' => $establishments->total(),
            ],
        ]);
    }

    /**
     * Cadastra um novo estabelecimento.
     */
    public function store(EstablishmentRequest $request): JsonResponse
    {
        $data = $request->safe()->except('state_id');
        $establishment = Establishment::query()->create($data);
        $establishment->load(['city.state:id,name,code', 'neighborhood:id,city_id,name']);

        AuditLogger::record(
            module: AuditLog::MODULE_ESTABLISHMENTS,
            action: AuditLog::ACTION_CREATED,
            description: "Estabelecimento {$establishment->name} criado.",
            auditable: $establishment,
            newValues: $establishment->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Estabelecimento criado com sucesso.',
            'establishment' => $establishment,
        ], 201);
    }

    /**
     * Atualiza um estabelecimento existente.
     */
    public function update(EstablishmentRequest $request, int $id): JsonResponse
    {
        $establishment = Establishment::query()->find($id);

        if (! $establishment) {
            return $this->notFound();
        }

        $data = $request->safe()->except('state_id');
        $oldValues = $establishment->only(array_keys($data));
        $establishment->update($data);
        $establishment->load(['city.state:id,name,code', 'neighborhood:id,city_id,name']);

        AuditLogger::record(
            module: AuditLog::MODULE_ESTABLISHMENTS,
            action: AuditLog::ACTION_UPDATED,
            description: "Estabelecimento {$establishment->name} atualizado.",
            auditable: $establishment,
            oldValues: $oldValues,
            newValues: $establishment->only(array_keys($data)),
            request: $request,
        );

        return response()->json([
            'message' => 'Estabelecimento atualizado com sucesso.',
            'establishment' => $establishment,
        ]);
    }

    /**
     * Exclui um estabelecimento.
     */
    public function destroy(int $id): JsonResponse
    {
        $establishment = Establishment::query()->find($id);

        if (! $establishment) {
            return $this->notFound();
        }

        AuditLogger::record(
            module: AuditLog::MODULE_ESTABLISHMENTS,
            action: AuditLog::ACTION_DELETED,
            description: "Estabelecimento {$establishment->name} excluído.",
            auditable: $establishment,
            oldValues: $establishment->toArray(),
        );

        $establishment->delete();

        return response()->json([
            'message' => 'Estabelecimento excluído com sucesso.',
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Estabelecimento não encontrado.',
        ], 404);
    }
}
