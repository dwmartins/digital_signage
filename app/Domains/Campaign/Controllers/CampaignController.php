<?php

namespace App\Domains\Campaign\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Campaign\Requests\CampaignRequest;
use App\Domains\Category\Models\Category;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Plan\Models\Plan;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', Rule::in($this->statuses())],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Campaign::query()->with([
            'customer:id,name,last_name,email',
            'categories:id,name',
            'mediaAssets:id,user_id,name,type,original_name,mime_type,size_bytes',
            'subscription.plan:id,name,billing_cycle',
        ]);

        if ($search = $validated['global'] ?? null) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($userId = $validated['user_id'] ?? null) {
            $query->where('user_id', $userId);
        }

        if ($categoryId = $validated['category_id'] ?? null) {
            $query->whereHas('categories', fn ($query) => $query->whereKey($categoryId));
        }

        if ($status = $validated['status'] ?? null) {
            $query->where('status', $status);
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

    public function options(): JsonResponse
    {
        return response()->json([
            'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name', 'last_name', 'email']),
            'categories' => Category::query()->where('status', Category::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']),
            'plans' => Plan::query()->where('status', Plan::STATUS_ACTIVE)->orderBy('price')->get(),
            'media_assets' => MediaAsset::query()
                ->where('processing_status', MediaAsset::PROCESSING_READY)
                ->whereIn('approval_status', [
                    MediaAsset::APPROVAL_PENDING,
                    MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
                    MediaAsset::APPROVAL_APPROVED,
                ])
                ->orderBy('name')
                ->get(['id', 'user_id', 'name', 'type', 'original_name', 'size_bytes', 'duration_seconds', 'width', 'height', 'approval_status']),
        ]);
    }

    public function store(CampaignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $mediaAsset = $this->validatedMediaAsset($data, $plan = Plan::query()->where('status', Plan::STATUS_ACTIVE)->findOrFail($data['plan_id']));

        if ($mediaAsset instanceof JsonResponse) {
            return $mediaAsset;
        }

        $campaign = DB::transaction(function () use ($data, $mediaAsset, $plan): Campaign {
            $campaign = Campaign::query()->create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => Campaign::STATUS_PENDING_APPROVAL,
            ]);
            $campaign->categories()->sync($data['category_ids'] ?? []);
            $campaign->mediaAssets()->sync([$mediaAsset->id => ['position' => 1]]);
            $campaign->subscription()->create($this->subscriptionSnapshot($plan));

            return $campaign;
        });

        $campaign->load(['customer', 'categories', 'mediaAssets', 'subscription.plan']);

        AuditLogger::record(
            module: AuditLog::MODULE_CAMPAIGNS,
            action: AuditLog::ACTION_CREATED,
            description: "Campanha {$campaign->name} criada com assinatura pendente.",
            auditable: $campaign,
            newValues: $campaign->toArray(),
            request: $request,
        );

        return response()->json([
            'message' => 'Campanha criada e assinatura gerada automaticamente.',
            'campaign' => $campaign,
        ], 201);
    }

    public function update(CampaignRequest $request, int $id): JsonResponse
    {
        $campaign = Campaign::query()->with('subscription.invoices')->find($id);

        if (! $campaign) {
            return $this->notFound();
        }

        $data = $request->validated();
        $plan = Plan::query()->where('status', Plan::STATUS_ACTIVE)->findOrFail($data['plan_id']);
        $mediaAsset = $this->validatedMediaAsset($data, $plan);

        if ($mediaAsset instanceof JsonResponse) {
            return $mediaAsset;
        }

        if ((int) $data['user_id'] !== $campaign->user_id
            && ($campaign->subscription?->status === CampaignSubscription::STATUS_ACTIVE
                || $campaign->subscription?->invoices->isNotEmpty())) {
            return response()->json([
                'message' => 'O cliente não pode ser alterado após a ativação ou geração de cobranças.',
            ], 422);
        }

        $oldValues = $campaign->load(['categories', 'mediaAssets', 'subscription'])->toArray();

        DB::transaction(function () use ($campaign, $data, $mediaAsset, $plan): void {
            $campaign->update([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);
            $campaign->categories()->sync($data['category_ids'] ?? []);
            $campaign->mediaAssets()->sync([$mediaAsset->id => ['position' => 1]]);

            if (! $campaign->subscription) {
                $campaign->subscription()->create($this->subscriptionSnapshot($plan));
            } elseif ($campaign->subscription->plan_id !== $plan->id) {
                $campaign->subscription->update([
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'screen_limit' => $plan->screen_limit,
                    'billing_cycle' => $plan->billing_cycle,
                    'media_type' => $plan->media_type,
                    'ends_at' => $campaign->subscription->starts_at
                        ? $this->calculateEndsAt($campaign->subscription->starts_at, $plan->billing_cycle)
                        : null,
                ]);
            }
        });

        $campaign->refresh()->load(['customer', 'categories', 'mediaAssets', 'subscription.plan']);

        AuditLogger::record(
            module: AuditLog::MODULE_CAMPAIGNS,
            action: AuditLog::ACTION_UPDATED,
            description: "Campanha {$campaign->name} atualizada.",
            auditable: $campaign,
            oldValues: $oldValues,
            newValues: $campaign->toArray(),
            request: $request,
        );

        return response()->json(['message' => 'Campanha atualizada com sucesso.', 'campaign' => $campaign]);
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = Campaign::query()->with('subscription.invoices')->find($id);

        if (! $campaign) {
            return $this->notFound();
        }

        if ($campaign->subscription?->invoices->isNotEmpty()) {
            return response()->json(['message' => 'Não é possível excluir uma campanha que possui histórico financeiro.'], 422);
        }

        AuditLogger::record(module: AuditLog::MODULE_CAMPAIGNS, action: AuditLog::ACTION_DELETED, description: "Campanha {$campaign->name} excluída.", auditable: $campaign, oldValues: $campaign->toArray());
        $campaign->delete();

        return response()->json(['message' => 'Campanha excluída com sucesso.']);
    }

    private function validatedMediaAsset(array $data, Plan $plan): mixed
    {
        $mediaAsset = MediaAsset::query()
            ->where('processing_status', MediaAsset::PROCESSING_READY)
            ->whereIn('approval_status', [
                MediaAsset::APPROVAL_PENDING,
                MediaAsset::APPROVAL_AWAITING_SUBSCRIPTION,
                MediaAsset::APPROVAL_APPROVED,
            ])
            ->where('user_id', $data['user_id'])
            ->where('type', $plan->media_type)
            ->find($data['media_asset_id']);

        if (! $mediaAsset) {
            return response()->json([
                'message' => 'A mídia deve estar revisada, pertencer ao cliente e possuir o tipo aceito pelo plano.',
            ], 422);
        }

        return $mediaAsset;
    }

    private function subscriptionSnapshot(Plan $plan): array
    {
        return [
            'plan_id' => $plan->id,
            'status' => CampaignSubscription::STATUS_PENDING,
            'price' => $plan->price,
            'screen_limit' => $plan->screen_limit,
            'billing_cycle' => $plan->billing_cycle,
            'media_type' => $plan->media_type,
            'starts_at' => null,
            'ends_at' => null,
            'cancelled_at' => null,
        ];
    }

    private function calculateEndsAt(mixed $startsAt, string $billingCycle): mixed
    {
        return $billingCycle === Plan::BILLING_YEARLY
            ? $startsAt->copy()->addYear()->endOfDay()
            : $startsAt->copy()->addDays(30)->endOfDay();
    }

    private function statuses(): array
    {
        return [
            Campaign::STATUS_DRAFT,
            Campaign::STATUS_PENDING_APPROVAL,
            Campaign::STATUS_APPROVED,
            Campaign::STATUS_REJECTED,
            Campaign::STATUS_ACTIVE,
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_COMPLETED,
            Campaign::STATUS_CANCELLED,
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Campanha não encontrada.'], 404);
    }
}
