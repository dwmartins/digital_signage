<?php

namespace App\Domains\Campaign\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Services\AuditLogger;
use App\Domains\Billing\Models\Invoice;
use App\Domains\Billing\Models\Transaction;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\Campaign\Requests\CampaignSubscriptionRequest;
use App\Domains\Campaign\Requests\RenewCampaignSubscriptionRequest;
use App\Domains\Media\Services\MediaStatusService;
use App\Domains\Plan\Models\Plan;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'status' => ['nullable', Rule::in($this->statuses())],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CampaignSubscription::query()->with(['customer:id,name,last_name,email', 'campaign:id,user_id,name,status', 'plan:id,name,billing_cycle']);
        if ($search = $validated['global'] ?? null) {
            $query->where(fn ($query) => $query->whereHas('campaign', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('customer', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(name, ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")));
        }
        foreach (['user_id', 'campaign_id', 'plan_id', 'status'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->where($field, $value);
            }
        }
        $items = $query->latest()->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json(['data' => $items->items(), 'pagination' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'campaigns' => Campaign::query()->with(['customer:id,name,last_name', 'mediaAssets:id,type'])->orderBy('name')->get(['id', 'user_id', 'name', 'status']),
            'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name', 'last_name', 'email']),
            'plans' => Plan::query()->orderBy('price')->get(),
            'statuses' => collect($this->statuses())->map(fn ($status) => ['value' => $status, 'label' => match ($status) {
                'pending' => 'Pendente', 'active' => 'Ativa', 'expired' => 'Vencida', default => 'Cancelada'
            }]),
        ]);
    }

    public function store(CampaignSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $plan = Plan::query()->where('status', Plan::STATUS_ACTIVE)->find($data['plan_id']);
        if (! $plan) {
            return response()->json(['message' => 'O plano selecionado não está disponível.'], 422);
        }
        $startsAt = isset($data['starts_at']) ? CarbonImmutable::parse($data['starts_at'])->startOfDay() : null;
        $subscription = CampaignSubscription::query()->create([
            ...$this->snapshot((int) $data['user_id'], $plan, CampaignSubscription::STATUS_PENDING),
            'price' => $data['price'], 'notes' => $data['notes'] ?? null, 'starts_at' => $startsAt,
            'ends_at' => isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at'])->endOfDay() : ($startsAt ? $this->calculateEndsAt($startsAt, $plan->billing_cycle) : null),
        ]);
        AuditLogger::record(module: AuditLog::MODULE_SUBSCRIPTIONS, action: AuditLog::ACTION_CREATED, description: "Assinatura #{$subscription->id} criada.", auditable: $subscription, newValues: $subscription->toArray(), request: $request);

        return response()->json(['message' => 'Assinatura criada. Agora ela pode ser vinculada a uma campanha.', 'subscription' => $subscription->load(['customer', 'plan'])], 201);
    }

    public function update(CampaignSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = CampaignSubscription::query()->with('campaign.mediaAssets')->find($id);
        if (! $subscription) {
            return response()->json(['message' => 'Assinatura não encontrada.'], 404);
        }
        $data = $request->validated();
        $status = $data['status'] ?? $subscription->status;

        if (! in_array($status, $this->allowedTransitions($subscription->status), true)) {
            return response()->json([
                'message' => 'A alteração de status solicitada não é permitida para esta assinatura.',
            ], 422);
        }

        if ($subscription->status === CampaignSubscription::STATUS_PENDING
            && $status === CampaignSubscription::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Use a ação Aprovar para ativar a assinatura e gerar a cobrança inicial.',
            ], 422);
        }

        $plan = Plan::query()->find($data['plan_id']);
        if (! $plan || ($plan->status !== Plan::STATUS_ACTIVE && $plan->id !== $subscription->plan_id)) {
            return response()->json(['message' => 'O plano selecionado não está disponível.'], 422);
        }

        if ($subscription->campaign && $subscription->campaign->mediaAssets->contains(fn ($media) => $media->type !== $plan->media_type)) {
            return response()->json([
                'message' => 'O tipo de mídia da campanha não é compatível com o plano selecionado.',
            ], 422);
        }

        if ($subscription->campaign && $subscription->campaign->mediaAssets->count() > $plan->media_limit) {
            return response()->json([
                'message' => "A campanha possui mais mídias do que o limite de {$plan->media_limit} permitido pelo plano selecionado.",
            ], 422);
        }

        $oldValues = $subscription->toArray();
        $startsAt = isset($data['starts_at']) ? CarbonImmutable::parse($data['starts_at'])->startOfDay() : null;
        $endsAt = isset($data['ends_at'])
            ? CarbonImmutable::parse($data['ends_at'])->endOfDay()
            : ($startsAt ? $this->calculateEndsAt($startsAt, $plan->billing_cycle) : null);

        $subscription->update([
            ...$this->snapshot($subscription->user_id, $plan, $status),
            'campaign_id' => $subscription->campaign_id,
            'price' => $data['price'],
            'notes' => $data['notes'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'cancelled_at' => $status === CampaignSubscription::STATUS_CANCELLED ? now() : null,
        ]);
        $subscription->campaign?->mediaAssets->each(fn ($media) => MediaStatusService::syncSubscriptionStatus($media));
        AuditLogger::record(module: AuditLog::MODULE_SUBSCRIPTIONS, action: AuditLog::ACTION_UPDATED, description: "Assinatura #{$subscription->id} atualizada.", auditable: $subscription, oldValues: $oldValues, newValues: $subscription->toArray(), request: $request);

        return response()->json(['message' => 'Assinatura atualizada com sucesso.', 'subscription' => $subscription->load(['campaign.customer', 'plan'])]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['nullable', Rule::in(Transaction::paymentMethods())],
            'payment_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $result = DB::transaction(function () use ($id, $request, $validated): array {
            $subscription = CampaignSubscription::query()->lockForUpdate()->with(['campaign.mediaAssets', 'invoices'])->find($id);
            if (! $subscription) {
                return ['error' => 'Assinatura não encontrada.', 'code' => 404];
            }
            if ($subscription->status !== CampaignSubscription::STATUS_PENDING) {
                return ['error' => 'Somente assinaturas pendentes podem ser aprovadas.', 'code' => 422];
            }
            $startsAt = $subscription->starts_at ?? now();
            $endsAt = $subscription->ends_at && $subscription->ends_at->greaterThan($startsAt)
                ? $subscription->ends_at
                : $this->calculateEndsAt($startsAt, $subscription->billing_cycle);
            $subscription->update(['status' => CampaignSubscription::STATUS_ACTIVE, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'cancelled_at' => null]);
            $subscription->campaign?->mediaAssets->each(fn ($media) => MediaStatusService::syncSubscriptionStatus($media));
            $invoice = null;
            $transaction = null;
            $alreadyCharged = $subscription->invoices->isNotEmpty();

            if ((float) $subscription->price > 0 && ! $alreadyCharged) {
                if (! isset($validated['payment_method'])) {
                    throw ValidationException::withMessages([
                        'payment_method' => 'Selecione o método de pagamento utilizado.',
                    ]);
                }

                if (! isset($validated['payment_date'])) {
                    throw ValidationException::withMessages([
                        'payment_date' => 'Informe a data em que o pagamento foi realizado.',
                    ]);
                }

                $paidAt = CarbonImmutable::parse($validated['payment_date'])->startOfDay();
                $invoice = Invoice::query()->create(['campaign_subscription_id' => $subscription->id, 'user_id' => $subscription->user_id, 'number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)), 'amount' => $subscription->price, 'status' => Invoice::STATUS_PAID, 'due_at' => $paidAt, 'paid_at' => $paidAt]);
                $transaction = Transaction::query()->create(['invoice_id' => $invoice->id, 'user_id' => $subscription->user_id, 'payment_method' => $validated['payment_method'], 'type' => Transaction::TYPE_CHARGE, 'status' => Transaction::STATUS_PAID, 'amount' => $subscription->price, 'processed_at' => $paidAt, 'metadata' => $subscription->notes ? ['notes' => $subscription->notes] : null]);
            }

            AuditLogger::record(module: AuditLog::MODULE_SUBSCRIPTIONS, action: AuditLog::ACTION_UPDATED, description: (float) $subscription->price > 0 ? "Assinatura #{$subscription->id} aprovada e paga." : "Assinatura gratuita #{$subscription->id} aprovada sem transação.", auditable: $subscription, newValues: $subscription->toArray(), request: $request);

            return ['subscription' => $subscription->load(['campaign.customer', 'plan']), 'invoice' => $invoice, 'transaction' => $transaction, 'already_charged' => $alreadyCharged];
        });
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        $message = $result['transaction']
            ? 'Assinatura aprovada e transação paga criada automaticamente.'
            : ($result['already_charged']
                ? 'Assinatura reativada sem gerar uma cobrança duplicada.'
                : 'Assinatura gratuita aprovada sem gerar transação.');

        return response()->json(['message' => $message, ...$result]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $subscription = CampaignSubscription::query()->with('campaign.mediaAssets')->find($id);
        if (! $subscription) {
            return response()->json(['message' => 'Assinatura não encontrada.'], 404);
        }
        if ($subscription->status === CampaignSubscription::STATUS_CANCELLED) {
            return response()->json(['message' => 'A assinatura já está cancelada.'], 422);
        }
        $subscription->update(['status' => CampaignSubscription::STATUS_CANCELLED, 'cancelled_at' => now()]);
        $subscription->campaign?->mediaAssets->each(fn ($media) => MediaStatusService::syncSubscriptionStatus($media));
        AuditLogger::record(module: AuditLog::MODULE_SUBSCRIPTIONS, action: AuditLog::ACTION_UPDATED, description: "Assinatura #{$subscription->id} cancelada.", auditable: $subscription, newValues: $subscription->toArray(), request: $request);

        return response()->json(['message' => 'Assinatura cancelada com sucesso.']);
    }

    /** Renova a vigência e registra uma nova cobrança paga. */
    public function renew(RenewCampaignSubscriptionRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($id, $request, $validated): array {
            $subscription = CampaignSubscription::query()
                ->lockForUpdate()
                ->with(['campaign.mediaAssets', 'plan'])
                ->find($id);

            if (! $subscription) {
                return ['error' => 'Assinatura não encontrada.', 'code' => 404];
            }

            if (! in_array($subscription->status, [
                CampaignSubscription::STATUS_ACTIVE,
                CampaignSubscription::STATUS_EXPIRED,
            ], true)) {
                return ['error' => 'Somente assinaturas ativas ou vencidas podem ser renovadas.', 'code' => 422];
            }

            $expectedEndsAt = isset($validated['expected_ends_at'])
                ? CarbonImmutable::parse($validated['expected_ends_at'])
                : null;

            if (($subscription->ends_at === null) !== ($expectedEndsAt === null)
                || ($subscription->ends_at && ! $subscription->ends_at->equalTo($expectedEndsAt))) {
                return [
                    'error' => 'A vigência foi alterada por outra operação. Atualize a listagem antes de renovar novamente.',
                    'code' => 409,
                ];
            }

            if ((float) $subscription->price > 0 && ! isset($validated['payment_method'])) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Selecione o método de pagamento utilizado.',
                ]);
            }

            $oldValues = $subscription->toArray();
            $previousEndsAt = $subscription->ends_at;
            $renewalReference = $previousEndsAt?->isFuture()
                ? $previousEndsAt->copy()
                : now();
            $newEndsAt = $this->calculateEndsAt($renewalReference, $subscription->billing_cycle);

            $subscription->update([
                'status' => CampaignSubscription::STATUS_ACTIVE,
                'starts_at' => $subscription->starts_at ?? now(),
                'ends_at' => $newEndsAt,
                'cancelled_at' => null,
            ]);

            $invoice = null;
            $transaction = null;

            if ((float) $subscription->price > 0) {
                $invoice = Invoice::query()->create([
                    'campaign_subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                    'amount' => $subscription->price,
                    'status' => Invoice::STATUS_PAID,
                    'due_at' => now(),
                    'paid_at' => now(),
                ]);
                $transaction = Transaction::query()->create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $subscription->user_id,
                    'payment_method' => $validated['payment_method'],
                    'type' => Transaction::TYPE_CHARGE,
                    'status' => Transaction::STATUS_PAID,
                    'amount' => $subscription->price,
                    'processed_at' => now(),
                    'metadata' => [
                        'renewal' => true,
                        'previous_ends_at' => $previousEndsAt?->toISOString(),
                        'new_ends_at' => $newEndsAt->toISOString(),
                        'notes' => $subscription->notes,
                    ],
                ]);
            }

            $subscription->campaign?->mediaAssets->each(
                fn ($media) => MediaStatusService::syncSubscriptionStatus($media),
            );

            AuditLogger::record(
                module: AuditLog::MODULE_SUBSCRIPTIONS,
                action: AuditLog::ACTION_UPDATED,
                description: (float) $subscription->price > 0
                    ? "Assinatura #{$subscription->id} renovada e paga."
                    : "Assinatura gratuita #{$subscription->id} renovada sem transação.",
                auditable: $subscription,
                oldValues: $oldValues,
                newValues: $subscription->toArray(),
                request: $request,
            );

            return [
                'subscription' => $subscription->load(['campaign.customer', 'plan']),
                'invoice' => $invoice,
                'transaction' => $transaction,
            ];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json([
            'message' => $result['transaction']
                ? 'Assinatura renovada e nova transação paga criada com sucesso.'
                : 'Assinatura gratuita renovada sem gerar transação.',
            ...$result,
        ]);
    }

    private function snapshot(int $userId, Plan $plan, string $status = CampaignSubscription::STATUS_PENDING): array
    {
        return ['user_id' => $userId, 'plan_id' => $plan->id, 'status' => $status, 'price' => $plan->price, 'screen_limit' => $plan->screen_limit, 'media_limit' => $plan->media_limit, 'billing_cycle' => $plan->billing_cycle, 'media_type' => $plan->media_type, 'starts_at' => null, 'ends_at' => null, 'cancelled_at' => null];
    }

    private function statuses(): array
    {
        return [CampaignSubscription::STATUS_PENDING, CampaignSubscription::STATUS_ACTIVE, CampaignSubscription::STATUS_EXPIRED, CampaignSubscription::STATUS_CANCELLED];
    }

    private function allowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            CampaignSubscription::STATUS_PENDING => [
                CampaignSubscription::STATUS_PENDING,
                CampaignSubscription::STATUS_CANCELLED,
            ],
            CampaignSubscription::STATUS_ACTIVE => [
                CampaignSubscription::STATUS_PENDING,
                CampaignSubscription::STATUS_ACTIVE,
                CampaignSubscription::STATUS_EXPIRED,
                CampaignSubscription::STATUS_CANCELLED,
            ],
            CampaignSubscription::STATUS_EXPIRED => [
                CampaignSubscription::STATUS_PENDING,
                CampaignSubscription::STATUS_EXPIRED,
                CampaignSubscription::STATUS_CANCELLED,
            ],
            default => [CampaignSubscription::STATUS_CANCELLED],
        };
    }

    private function calculateEndsAt(mixed $startsAt, string $billingCycle): mixed
    {
        return $billingCycle === Plan::BILLING_YEARLY
            ? $startsAt->copy()->addYear()->endOfDay()
            : $startsAt->copy()->addDays(30)->endOfDay();
    }
}
