<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Billing\Models\Transaction;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Models\CampaignSubscription;
use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Player\Models\Player;
use App\Domains\Screen\Models\Screen;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;

class PlatformDashboardController
{
    /** Retorna os principais indicadores operacionais e comerciais da plataforma. */
    public function index(): JsonResponse
    {
        $connectionThreshold = now()->subSeconds(Player::CONNECTION_TIMEOUT_SECONDS);
        $displayPoints = DisplayPoint::query()
            ->with([
                'establishment:id,name',
                'player:id,name,last_seen_at',
            ])
            ->get();

        $attentionPoints = $displayPoints
            ->filter(fn (DisplayPoint $point) => $this->needsAttention($point, $connectionThreshold))
            ->sortBy(fn (DisplayPoint $point) => $this->attentionPriority($point, $connectionThreshold))
            ->take(6)
            ->values()
            ->map(fn (DisplayPoint $point) => [
                'id' => $point->id,
                'name' => $point->name,
                'location' => $point->location,
                'status' => $point->status,
                'establishment' => $point->establishment?->name,
                'player' => $point->player?->name,
                'last_seen_at' => $point->player?->last_seen_at,
                'reason' => $this->attentionReason($point, $connectionThreshold),
            ]);

        $onlinePoints = $displayPoints->filter(
            fn (DisplayPoint $point) => $point->player?->last_seen_at?->greaterThanOrEqualTo($connectionThreshold),
        )->count();
        $withoutPlayer = $displayPoints->whereNull('player_id')->count();
        $offlinePoints = $displayPoints->count() - $onlinePoints - $withoutPlayer;
        $totalPoints = $displayPoints->count();

        $monthlyRevenue = Transaction::query()
            ->where('type', Transaction::TYPE_CHARGE)
            ->where('status', Transaction::STATUS_PAID)
            ->whereBetween('processed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $recentTransactions = Transaction::query()
            ->with('customer:id,name,last_name')
            ->where('type', Transaction::TYPE_CHARGE)
            ->where('status', Transaction::STATUS_PAID)
            ->latest('processed_at')
            ->limit(5)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'customer' => $transaction->customer?->full_name,
                'amount' => $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'processed_at' => $transaction->processed_at,
            ]);

        return response()->json([
            'summary' => [
                'active_campaigns' => Campaign::query()->where('status', Campaign::STATUS_ACTIVE)->count(),
                'monthly_revenue' => $monthlyRevenue,
                'active_subscriptions' => CampaignSubscription::query()->where('status', CampaignSubscription::STATUS_ACTIVE)->count(),
                'screens_in_stock' => Screen::query()->where('status', Screen::STATUS_STOCK)->count(),
                'players_in_stock' => Player::query()->where('status', Player::STATUS_STOCK)->count(),
                'display_points' => $totalPoints,
                'attention_points' => $displayPoints->filter(fn (DisplayPoint $point) => $this->needsAttention($point, $connectionThreshold))->count(),
                'pending_media' => MediaAsset::query()->where('approval_status', MediaAsset::APPROVAL_PENDING)->count(),
            ],
            'subscription_growth' => $this->subscriptionGrowth(),
            'network_health' => [
                'online' => $onlinePoints,
                'offline' => $offlinePoints,
                'without_player' => $withoutPlayer,
                'total' => $totalPoints,
                'online_percentage' => $totalPoints > 0 ? round(($onlinePoints / $totalPoints) * 100, 1) : 0,
            ],
            'attention_points' => $attentionPoints,
            'recent_transactions' => $recentTransactions,
            'generated_at' => now(),
        ]);
    }

    /** Monta a evolução das assinaturas vigentes ao final de cada um dos últimos 12 meses. */
    private function subscriptionGrowth(): array
    {
        $subscriptions = CampaignSubscription::query()
            ->whereNotNull('starts_at')
            ->select(['starts_at', 'ends_at', 'cancelled_at'])
            ->get();
        $months = collect(range(11, 1))->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth())
            ->push(now()->startOfMonth());
        $monthNames = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

        return [
            'labels' => $months->map(fn ($month) => $monthNames[$month->month - 1].'/'.$month->format('y')),
            'values' => $months->map(function ($month) use ($subscriptions) {
                $reference = $month->isSameMonth(now()) ? now() : $month->copy()->endOfMonth();

                return $subscriptions->filter(fn (CampaignSubscription $subscription) =>
                    $subscription->starts_at->lessThanOrEqualTo($reference)
                    && (!$subscription->ends_at || $subscription->ends_at->greaterThanOrEqualTo($reference))
                    && (!$subscription->cancelled_at || $subscription->cancelled_at->greaterThan($reference))
                )->count();
            }),
        ];
    }

    private function needsAttention(DisplayPoint $point, CarbonInterface $threshold): bool
    {
        return $point->status !== DisplayPoint::STATUS_ACTIVE
            || !$point->player
            || !$point->player->last_seen_at
            || $point->player->last_seen_at->lessThan($threshold);
    }

    private function attentionPriority(DisplayPoint $point, CarbonInterface $threshold): int
    {
        return match ($this->attentionReason($point, $threshold)) {
            'never_connected' => 1,
            'offline' => 2,
            'without_player' => 3,
            'maintenance' => 4,
            default => 5,
        };
    }

    private function attentionReason(DisplayPoint $point, CarbonInterface $threshold): string
    {
        if (!$point->player) {
            return 'without_player';
        }

        if (!$point->player->last_seen_at) {
            return 'never_connected';
        }

        if ($point->player->last_seen_at->lessThan($threshold)) {
            return 'offline';
        }

        return $point->status;
    }
}
