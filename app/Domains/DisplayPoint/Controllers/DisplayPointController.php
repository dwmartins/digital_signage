<?php

namespace App\Domains\DisplayPoint\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\DisplayPoint\Requests\DisplayPointRequest;
use App\Domains\Establishment\Models\Establishment;
use App\Domains\Player\Models\Player;
use App\Domains\Screen\Models\Screen;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisplayPointController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'establishment_id' => ['nullable', 'integer', 'exists:establishments,id'],
            'orientation' => ['nullable', Rule::in([
                DisplayPoint::ORIENTATION_LANDSCAPE,
                DisplayPoint::ORIENTATION_PORTRAIT,
            ])],
            'status' => ['nullable', Rule::in([
                DisplayPoint::STATUS_ACTIVE,
                DisplayPoint::STATUS_MAINTENANCE,
                DisplayPoint::STATUS_INACTIVE,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DisplayPoint::query()->with([
            'establishment.city.state:id,name,code',
            'screen:id,name,code',
            'player:id,name,code,last_seen_at',
        ]);
        if ($search = $validated['global'] ?? null) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%"));
        }
        foreach (['establishment_id', 'orientation', 'status'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->where($field, $value);
            }
        }
        $items = $query->latest()->paginate((int) ($validated['perPage'] ?? 7));

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
        $validated = $request->validate(['id' => ['nullable', 'integer', 'exists:display_points,id']]);
        $id = $validated['id'] ?? null;

        return response()->json([
            'establishments' => Establishment::query()
                ->with('city.state:id,name,code')
                ->orderBy('name')
                ->get(['id', 'name', 'city_id']),
            'screens' => Screen::query()
                ->where(fn ($query) => $query
                    ->whereDoesntHave('displayPoint')
                    ->when($id, fn ($query) => $query->orWhereHas('displayPoint', fn ($query) => $query->whereKey($id))))
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'players' => Player::query()
                ->where(fn ($query) => $query
                    ->whereDoesntHave('displayPoint')
                    ->when($id, fn ($query) => $query->orWhereHas('displayPoint', fn ($query) => $query->whereKey($id))))
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(DisplayPointRequest $request): JsonResponse
    {
        $item = DisplayPoint::query()->create($request->validated());
        AuditLogger::record(AuditLog::MODULE_DISPLAY_POINTS, AuditLog::ACTION_CREATED, "Ponto {$item->name} criado.", $item, newValues: $item->toArray(), request: $request);

        return response()->json(['message' => 'Ponto de exibição criado com sucesso.', 'display_point' => $item], 201);
    }

    public function update(DisplayPointRequest $request, int $id): JsonResponse
    {
        $item = DisplayPoint::query()->find($id);
        if (! $item) {
            return $this->notFound();
        }

        $old = $item->toArray();
        $item->update($request->validated());
        AuditLogger::record(AuditLog::MODULE_DISPLAY_POINTS, AuditLog::ACTION_UPDATED, "Ponto {$item->name} atualizado.", $item, $old, $item->toArray(), request: $request);

        return response()->json(['message' => 'Ponto de exibição atualizado com sucesso.', 'display_point' => $item]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = DisplayPoint::query()->find($id);
        if (! $item) {
            return $this->notFound();
        }

        AuditLogger::record(AuditLog::MODULE_DISPLAY_POINTS, AuditLog::ACTION_DELETED, "Ponto {$item->name} excluído.", $item, $item->toArray());
        $item->delete();

        return response()->json(['message' => 'Ponto de exibição excluído com sucesso.']);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Ponto de exibição não encontrado.'], 404);
    }
}
