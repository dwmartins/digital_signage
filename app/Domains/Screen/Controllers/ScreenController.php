<?php

namespace App\Domains\Screen\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Establishment\Models\Establishment;
use App\Domains\Screen\Models\Screen;
use App\Domains\Screen\Requests\ScreenRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScreenController extends Controller
{
    /**
     * Lista as telas com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'establishment_id' => ['nullable', 'integer', 'exists:establishments,id'],
            'display_point_id' => ['nullable', 'integer', 'exists:display_points,id'],
            'status' => ['nullable', Rule::in([
                Screen::STATUS_ACTIVE,
                Screen::STATUS_MAINTENANCE,
                Screen::STATUS_BLOCKED,
                Screen::STATUS_STOCK,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Screen::query()->with('displayPoint:id,screen_id,name');

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        foreach (['status'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->where($field, $value);
            }
        }

        if ($establishmentId = $validated['establishment_id'] ?? null) {
            $query->whereHas('displayPoint', fn ($query) => $query->where('establishment_id', $establishmentId));
        }

        if ($displayPointId = $validated['display_point_id'] ?? null) {
            $query->whereHas('displayPoint', fn ($query) => $query->whereKey($displayPointId));
        }

        $screens = $query
            ->latest()
            ->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json([
            'data' => $screens->items(),
            'pagination' => [
                'current_page' => $screens->currentPage(),
                'last_page' => $screens->lastPage(),
                'per_page' => $screens->perPage(),
                'total' => $screens->total(),
            ],
        ]);
    }

    /**
     * Retorna as opções utilizadas nos filtros de telas.
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json([
            'establishments' => Establishment::query()
                ->with('city.state:id,name,code')
                ->orderBy('name')
                ->get(['id', 'name', 'city_id']),
            'display_points' => DisplayPoint::query()->orderBy('name')->get(['id', 'establishment_id', 'name']),
        ]);
    }

    /**
     * Cadastra uma nova tela.
     */
    public function store(ScreenRequest $request): JsonResponse
    {
        $screen = Screen::query()->create($request->validated());
        $screen->load('displayPoint:id,screen_id,name');

        AuditLogger::record(
            module: AuditLog::MODULE_SCREENS,
            action: AuditLog::ACTION_CREATED,
            description: "Tela {$screen->name} criada.",
            auditable: $screen,
            newValues: $screen->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Tela criada com sucesso.',
            'screen' => $screen,
        ], 201);
    }

    /**
     * Atualiza uma tela existente.
     */
    public function update(ScreenRequest $request, int $id): JsonResponse
    {
        $screen = Screen::query()->find($id);

        if (! $screen) {
            return $this->notFound();
        }

        $oldValues = $screen->only(array_keys($request->validated()));
        $screen->update($request->validated());
        $screen->load('displayPoint:id,screen_id,name');

        AuditLogger::record(
            module: AuditLog::MODULE_SCREENS,
            action: AuditLog::ACTION_UPDATED,
            description: "Tela {$screen->name} atualizada.",
            auditable: $screen,
            oldValues: $oldValues,
            newValues: $screen->only(array_keys($request->validated())),
            request: $request,
        );

        return response()->json([
            'message' => 'Tela atualizada com sucesso.',
            'screen' => $screen,
        ]);
    }

    /**
     * Exclui uma tela.
     */
    public function destroy(int $id): JsonResponse
    {
        $screen = Screen::query()->find($id);

        if (! $screen) {
            return $this->notFound();
        }

        AuditLogger::record(
            module: AuditLog::MODULE_SCREENS,
            action: AuditLog::ACTION_DELETED,
            description: "Tela {$screen->name} excluída.",
            auditable: $screen,
            oldValues: $screen->toArray(),
        );

        $screen->delete();

        return response()->json(['message' => 'Tela excluída com sucesso.']);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Tela não encontrada.'], 404);
    }
}
