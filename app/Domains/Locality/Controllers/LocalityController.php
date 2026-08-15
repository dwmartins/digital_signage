<?php

namespace App\Domains\Locality\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Locality\Models\City;
use App\Domains\Locality\Models\Neighborhood;
use App\Domains\Locality\Models\State;
use App\Domains\Locality\Requests\LocalityRequest;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocalityController extends Controller
{
    public function index(Request $request, string $type): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                State::STATUS_ACTIVE,
                State::STATUS_INACTIVE,
            ])],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->queryForType($type);

        if ($search = $validated['global'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($type === 'cities' && $stateId = $validated['state_id'] ?? null) {
            $query->where('state_id', $stateId);
        }

        if ($type === 'neighborhoods') {
            if ($cityId = $validated['city_id'] ?? null) {
                $query->where('city_id', $cityId);
            }

            if ($stateId = $validated['state_id'] ?? null) {
                $query->whereHas('city', fn (Builder $query) => $query->where('state_id', $stateId));
            }
        }

        $items = $query
            ->orderBy('name')
            ->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);

        $includeInactive = (bool) ($validated['include_inactive'] ?? false);
        $states = State::query()
            ->when(! $includeInactive, fn (Builder $query) => $query->where('status', State::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'status']);

        $cities = City::query()
            ->with('state:id,name,code')
            ->when(! $includeInactive, fn (Builder $query) => $query->where('status', City::STATUS_ACTIVE))
            ->when($validated['state_id'] ?? null, fn (Builder $query, int $stateId) => $query->where('state_id', $stateId))
            ->orderBy('name')
            ->get(['id', 'state_id', 'name', 'status']);

        $neighborhoods = Neighborhood::query()
            ->with('city:id,state_id,name')
            ->when(! $includeInactive, fn (Builder $query) => $query->where('status', Neighborhood::STATUS_ACTIVE))
            ->when($validated['city_id'] ?? null, fn (Builder $query, int $cityId) => $query->where('city_id', $cityId))
            ->orderBy('name')
            ->get(['id', 'city_id', 'name', 'status']);

        return response()->json(compact('states', 'cities', 'neighborhoods'));
    }

    public function store(LocalityRequest $request, string $type): JsonResponse
    {
        $model = $this->modelClass($type);
        $locality = $model::query()->create($this->dataForType($request, $type));

        AuditLogger::record(
            module: AuditLog::MODULE_LOCALITIES,
            action: AuditLog::ACTION_CREATED,
            description: "{$this->labelForType($type)} {$locality->name} criado(a).",
            auditable: $locality,
            newValues: $locality->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Localidade criada com sucesso.',
            'locality' => $this->loadRelations($locality, $type),
        ], 201);
    }

    public function update(LocalityRequest $request, string $type, int $id): JsonResponse
    {
        $locality = $this->queryForType($type)->find($id);

        if (! $locality) {
            return $this->notFound();
        }

        $data = $this->dataForType($request, $type);
        $oldValues = $locality->only(array_keys($data));
        $locality->update($data);

        AuditLogger::record(
            module: AuditLog::MODULE_LOCALITIES,
            action: AuditLog::ACTION_UPDATED,
            description: "{$this->labelForType($type)} {$locality->name} atualizado(a).",
            auditable: $locality,
            oldValues: $oldValues,
            newValues: $locality->only(array_keys($data)),
            request: $request,
        );

        return response()->json([
            'message' => 'Localidade atualizada com sucesso.',
            'locality' => $this->loadRelations($locality, $type),
        ]);
    }

    public function destroy(Request $request, string $type, int $id): JsonResponse
    {
        $locality = $this->queryForType($type)->find($id);

        if (! $locality) {
            return $this->notFound();
        }

        if ($this->isInUse($locality, $type)) {
            return response()->json([
                'message' => 'Não é possível excluir uma localidade que possui vínculos.',
            ], 422);
        }

        AuditLogger::record(
            module: AuditLog::MODULE_LOCALITIES,
            action: AuditLog::ACTION_DELETED,
            description: "{$this->labelForType($type)} {$locality->name} excluído(a).",
            auditable: $locality,
            oldValues: $locality->toArray(),
            request: $request,
        );

        $locality->delete();

        return response()->json([
            'message' => 'Localidade excluída com sucesso.',
        ]);
    }

    private function queryForType(string $type): Builder
    {
        return match ($type) {
            'states' => State::query(),
            'cities' => City::query()->with('state:id,name,code'),
            'neighborhoods' => Neighborhood::query()->with('city.state:id,name,code'),
            default => abort(404),
        };
    }

    private function modelClass(string $type): string
    {
        return match ($type) {
            'states' => State::class,
            'cities' => City::class,
            'neighborhoods' => Neighborhood::class,
            default => abort(404),
        };
    }

    private function dataForType(LocalityRequest $request, string $type): array
    {
        return match ($type) {
            'states' => $request->safe()->only(['name', 'code', 'status']),
            'cities' => $request->safe()->only(['state_id', 'name', 'status']),
            'neighborhoods' => $request->safe()->only(['city_id', 'name', 'status']),
            default => abort(404),
        };
    }

    private function loadRelations(Model $locality, string $type): Model
    {
        return match ($type) {
            'cities' => $locality->load('state:id,name,code'),
            'neighborhoods' => $locality->load('city.state:id,name,code'),
            default => $locality,
        };
    }

    private function isInUse(Model $locality, string $type): bool
    {
        return match ($type) {
            'states' => $locality->cities()->exists(),
            'cities' => $locality->neighborhoods()->exists() || $locality->establishments()->exists(),
            'neighborhoods' => $locality->establishments()->exists(),
            default => true,
        };
    }

    private function labelForType(string $type): string
    {
        return match ($type) {
            'states' => 'Estado',
            'cities' => 'Cidade',
            'neighborhoods' => 'Bairro',
            default => 'Localidade',
        };
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Localidade não encontrada.',
        ], 404);
    }
}
