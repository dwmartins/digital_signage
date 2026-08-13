<?php

namespace App\Domains\Plan\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Plan\Models\Plan;
use App\Domains\Plan\Requests\PlanRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Plan::query()->withCount('subscriptions')->orderBy('price')->get()]);
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);
        $plan = Plan::query()->create($data);

        AuditLogger::record(module: AuditLog::MODULE_PLANS, action: AuditLog::ACTION_CREATED, description: "Plano {$plan->name} criado.", auditable: $plan, newValues: $plan->toArray(), request: $request);

        return response()->json(['message' => 'Plano criado com sucesso.', 'plan' => $plan], 201);
    }

    public function update(PlanRequest $request, int $id): JsonResponse
    {
        $plan = Plan::query()->find($id);
        if (! $plan) {
            return response()->json(['message' => 'Plano não encontrado.'], 404);
        }
        $oldValues = $plan->toArray();
        $plan->update($request->validated());
        AuditLogger::record(module: AuditLog::MODULE_PLANS, action: AuditLog::ACTION_UPDATED, description: "Plano {$plan->name} atualizado.", auditable: $plan, oldValues: $oldValues, newValues: $plan->toArray(), request: $request);

        return response()->json(['message' => 'Plano atualizado com sucesso.', 'plan' => $plan]);
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = Plan::query()->find($id);
        if (! $plan) {
            return response()->json(['message' => 'Plano não encontrado.'], 404);
        }
        if ($plan->subscriptions()->exists()) {
            return response()->json(['message' => 'Não é possível excluir um plano que possui assinaturas.'], 422);
        }
        AuditLogger::record(module: AuditLog::MODULE_PLANS, action: AuditLog::ACTION_DELETED, description: "Plano {$plan->name} excluído.", auditable: $plan, oldValues: $plan->toArray());
        $plan->delete();

        return response()->json(['message' => 'Plano excluído com sucesso.']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'plano';
        $slug = $base;
        $suffix = 2;
        while (Plan::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
}
