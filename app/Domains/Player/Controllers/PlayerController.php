<?php

namespace App\Domains\Player\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Establishment\Models\Establishment;
use App\Domains\Player\Models\Player;
use App\Domains\Player\Requests\PlayerRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'establishment_id' => ['nullable', 'integer', 'exists:establishments,id'],
            'display_point_id' => ['nullable', 'integer', 'exists:display_points,id'],
            'status' => ['nullable', Rule::in([Player::STATUS_ACTIVE, Player::STATUS_MAINTENANCE, Player::STATUS_BLOCKED, Player::STATUS_STOCK])],
            'page' => ['nullable', 'integer', 'min:1'], 'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Player::query()->with('displayPoint:id,player_id,name');
        if ($search = $validated['global'] ?? null) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('hostname', 'like', "%{$search}%"));
        }
        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($establishmentId = $validated['establishment_id'] ?? null) {
            $query->whereHas('displayPoint', fn ($query) => $query->where('establishment_id', $establishmentId));
        }

        if ($displayPointId = $validated['display_point_id'] ?? null) {
            $query->whereHas('displayPoint', fn ($query) => $query->whereKey($displayPointId));
        }
        $items = $query->latest()->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json(['data' => $items->items(), 'pagination' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

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

    public function store(PlayerRequest $request): JsonResponse
    {
        $player = Player::query()->create($request->validated());
        AuditLogger::record(AuditLog::MODULE_PLAYERS, AuditLog::ACTION_CREATED, "Player (PC) {$player->name} criado.", $player, newValues: $player->toArray(), request: $request);

        return response()->json(['message' => 'Player (PC) criado com sucesso.', 'player' => $player], 201);
    }

    public function update(PlayerRequest $request, int $id): JsonResponse
    {
        $player = Player::query()->find($id);
        if (! $player) {
            return $this->notFound();
        }
        $old = $player->only(array_keys($request->validated()));
        $player->update($request->validated());
        AuditLogger::record(AuditLog::MODULE_PLAYERS, AuditLog::ACTION_UPDATED, "Player (PC) {$player->name} atualizado.", $player, $old, $player->only(array_keys($request->validated())), request: $request);

        return response()->json(['message' => 'Player (PC) atualizado com sucesso.', 'player' => $player]);
    }

    public function destroy(int $id): JsonResponse
    {
        $player = Player::query()->find($id);
        if (! $player) {
            return $this->notFound();
        }
        AuditLogger::record(AuditLog::MODULE_PLAYERS, AuditLog::ACTION_DELETED, "Player (PC) {$player->name} excluído.", $player, $player->toArray());
        $player->delete();

        return response()->json(['message' => 'Player (PC) excluído com sucesso.']);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Player (PC) não encontrado.'], 404);
    }
}
