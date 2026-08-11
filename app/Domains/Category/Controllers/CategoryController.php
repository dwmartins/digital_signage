<?php

namespace App\Domains\Category\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Category\Models\Category;
use App\Domains\Category\Requests\CategoryRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Lista as categorias com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                Category::STATUS_ACTIVE,
                Category::STATUS_INACTIVE,
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Category::query();

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
        }

        $categories = $query
            ->orderBy('name')
            ->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json([
            'data' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Cadastra uma nova categoria.
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        AuditLogger::record(
            module: AuditLog::MODULE_CATEGORIES,
            action: AuditLog::ACTION_CREATED,
            description: "Categoria {$category->name} criada.",
            auditable: $category,
            newValues: $category->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Categoria criada com sucesso.',
            'category' => $category,
        ], 201);
    }

    /**
     * Atualiza uma categoria existente.
     */
    public function update(CategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::query()->find($id);

        if (!$category) {
            return $this->notFound();
        }

        $oldValues = $category->only(array_keys($request->validated()));
        $category->update($request->validated());

        AuditLogger::record(
            module: AuditLog::MODULE_CATEGORIES,
            action: AuditLog::ACTION_UPDATED,
            description: "Categoria {$category->name} atualizada.",
            auditable: $category,
            oldValues: $oldValues,
            newValues: $category->only(array_keys($request->validated())),
            request: $request,
        );

        return response()->json([
            'message' => 'Categoria atualizada com sucesso.',
            'category' => $category,
        ]);
    }

    /**
     * Exclui uma categoria.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::query()->find($id);

        if (! $category) {
            return $this->notFound();
        }

        AuditLogger::record(
            module: AuditLog::MODULE_CATEGORIES,
            action: AuditLog::ACTION_DELETED,
            description: "Categoria {$category->name} excluída.",
            auditable: $category,
            oldValues: $category->toArray(),
        );

        $category->delete();

        return response()->json([
            'message' => 'Categoria excluída com sucesso.',
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Categoria não encontrada.',
        ], 404);
    }
}
