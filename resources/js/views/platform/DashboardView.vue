<script setup>
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import { showAlert } from "@/helpers/alert";
import dashboardService from "@/services/dashboard.service";
import { useAuthStore } from "@/stores/authStore";
import Chart from "primevue/chart";
import Skeleton from "primevue/skeleton";
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);
const refreshing = ref(false);
const dashboard = ref(null);
const loadFailed = ref(false);

const summary = computed(() => dashboard.value?.summary ?? {});

const metricCards = computed(() => [
    { label: "Campanhas ativas", value: summary.value.active_campaigns ?? 0, icon: "pi pi-megaphone", tone: "primary", route: "platform.campaigns", permission: "campaigns.view" },
    { label: "Assinaturas ativas", value: summary.value.active_subscriptions ?? 0, icon: "pi pi-calendar-clock", tone: "success", route: "platform.subscriptions", permission: "subscriptions.view" },
    { label: "Pontos de exibição", value: summary.value.display_points ?? 0, icon: "pi pi-map-marker", tone: "primary", route: "platform.display-points", permission: "display-points.view" },
    { label: "Pontos que exigem atenção", value: summary.value.attention_points ?? 0, icon: "pi pi-exclamation-triangle", tone: "danger", route: "platform.display-points", permission: "display-points.view" },
    { label: "Mídias aguardando análise", value: summary.value.pending_media ?? 0, icon: "pi pi-images", tone: "warning", route: "platform.media", permission: "media.view", query: { approval_status: "pending_approval" } },
]);

const chartData = computed(() => ({
    labels: dashboard.value?.subscription_growth?.labels ?? [],
    datasets: [{
        label: "Assinaturas ativas",
        data: dashboard.value?.subscription_growth?.values ?? [],
        borderColor: "#7c3aed",
        backgroundColor: "rgba(124, 58, 237, 0.12)",
        pointBackgroundColor: "#7c3aed",
        pointBorderColor: "#ffffff",
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 3,
        fill: true,
        tension: 0.35,
    }],
}));

const chartOptions = computed(() => {
    const styles = getComputedStyle(document.documentElement);
    const textColor = styles.getPropertyValue("--p-text-muted-color") || "#64748b";
    const gridColor = styles.getPropertyValue("--p-content-border-color") || "#e2e8f0";

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: "index" },
        plugins: {
            legend: { display: false },
            tooltip: { padding: 12, displayColors: false },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: textColor },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                ticks: { color: textColor, precision: 0 },
                grid: { color: gridColor },
                border: { display: false },
            },
        },
    };
});

const currency = (value) => Number(value ?? 0).toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
});

const dateTime = (value) => value
    ? new Intl.DateTimeFormat("pt-BR", { dateStyle: "short", timeStyle: "short" }).format(new Date(value))
    : "Nunca conectado";

const paymentMethod = (value) => ({
    pix: "PIX",
    credit_card: "Cartão de crédito",
    debit_card: "Cartão de débito",
    bank_slip: "Boleto",
    bank_transfer: "Transferência",
    cash: "Dinheiro",
})[value] ?? "Não informado";

const attentionLabel = (reason) => ({
    offline: "Sem comunicação",
    never_connected: "Nunca conectado",
    without_player: "Sem player",
    maintenance: "Em manutenção",
    inactive: "Inativo",
})[reason] ?? "Requer atenção";

const attentionSeverity = (reason) => ({
    offline: "danger",
    never_connected: "danger",
    without_player: "warn",
    maintenance: "warn",
    inactive: "secondary",
})[reason] ?? "warn";

const canAccess = (permission) => auth.hasPermission(permission);

const openMetric = (metric) => {
    if (!metric.route || !canAccess(metric.permission)) return;
    router.push({ name: metric.route, query: metric.query });
};

const fetchDashboard = async (isRefresh = false) => {
    try {
        isRefresh ? (refreshing.value = true) : (loading.value = true);
        loadFailed.value = false;
        dashboard.value = await dashboardService.index();
    } catch (error) {
        loadFailed.value = true;
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

onMounted(() => fetchDashboard());
</script>

<template>
    <section class="container pb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <Breadcrumb :items="[{ icon: 'pi pi-home', to: '/' }, { label: 'Dashboard' }]" />
                <div class="mt-3">
                    <h1 class="h4 fw-bold mb-1">Visão geral da plataforma</h1>
                    <p class="text-muted mb-0">Acompanhe resultados comerciais e a operação da rede.</p>
                </div>
            </div>

            <Button
                label="Atualizar dados"
                icon="pi pi-refresh"
                severity="secondary"
                outlined
                size="small"
                :loading="refreshing"
                @click="fetchDashboard(true)"
            />
        </div>

        <template v-if="loading">
            <div class="row g-3 mb-4">
                <div v-for="index in 8" :key="index" class="col-12 col-sm-6 col-xl-3">
                    <Card class="h-100 border-0 shadow-sm">
                        <template #content>
                            <div class="d-flex justify-content-between gap-3">
                                <div class="flex-grow-1">
                                    <Skeleton width="70%" height="0.9rem" class="mb-3" />
                                    <Skeleton width="42%" height="1.8rem" />
                                </div>
                                <Skeleton shape="circle" size="3rem" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-8"><Skeleton height="24rem" /></div>
                <div class="col-12 col-xl-4"><Skeleton height="24rem" /></div>
            </div>
        </template>

        <template v-else-if="dashboard">
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <Card
                        class="revenue-card h-100 border-0 text-white overflow-hidden"
                        :class="{ 'metric-clickable': canAccess('transactions.view') }"
                        @click="canAccess('transactions.view') && router.push({ name: 'platform.transactions' })"
                    >
                        <template #content>
                            <div class="d-flex justify-content-between align-items-start gap-3 position-relative">
                                <div>
                                    <span class="d-block small opacity-75 mb-2">Arrecadado neste mês</span>
                                    <strong class="d-block fs-3">{{ currency(summary.monthly_revenue) }}</strong>
                                    <small class="d-block mt-2 opacity-75">Transações pagas no período atual</small>
                                </div>
                                <span class="metric-icon revenue-icon flex-shrink-0">
                                    <i class="pi pi-money-bill"></i>
                                </span>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <Card
                        class="pending-subscription-card h-100 border-0 overflow-hidden"
                        :class="{
                            'has-pending': (summary.pending_subscriptions ?? 0) > 0,
                            'metric-clickable': canAccess('subscriptions.view'),
                        }"
                        @click="canAccess('subscriptions.view') && router.push({
                            name: 'platform.subscriptions',
                            query: { status: 'pending' },
                        })"
                    >
                        <template #content>
                            <div class="d-flex justify-content-between align-items-start gap-3 position-relative">
                                <div class="min-w-0">
                                    <span class="d-block small mb-2">Assinaturas pendentes</span>
                                    <strong class="d-block fs-3">{{ summary.pending_subscriptions ?? 0 }}</strong>
                                    <small class="d-block mt-2">
                                        {{ (summary.pending_subscriptions ?? 0) > 0
                                            ? "Novos clientes aguardando análise"
                                            : "Nenhuma contratação aguardando" }}
                                    </small>
                                </div>
                                <span class="pending-subscription-icon metric-icon flex-shrink-0">
                                    <i :class="(summary.pending_subscriptions ?? 0) > 0 ? 'pi pi-bell' : 'pi pi-check'"></i>
                                </span>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <Card
                        class="expired-subscription-card h-100 border-0 overflow-hidden"
                        :class="{
                            'has-expired': (summary.expired_subscriptions ?? 0) > 0,
                            'metric-clickable': canAccess('subscriptions.view'),
                        }"
                        @click="canAccess('subscriptions.view') && router.push({
                            name: 'platform.subscriptions',
                            query: { status: 'expired' },
                        })"
                    >
                        <template #content>
                            <div class="d-flex justify-content-between align-items-start gap-3 position-relative">
                                <div class="min-w-0">
                                    <span class="d-block small mb-2">Assinaturas vencidas</span>
                                    <strong class="d-block fs-3">{{ summary.expired_subscriptions ?? 0 }}</strong>
                                    <small class="d-block mt-2">
                                        {{ (summary.expired_subscriptions ?? 0) > 0
                                            ? "Contratações aguardando renovação"
                                            : "Nenhuma assinatura vencida" }}
                                    </small>
                                </div>
                                <span class="expired-subscription-icon metric-icon flex-shrink-0">
                                    <i :class="(summary.expired_subscriptions ?? 0) > 0 ? 'pi pi-calendar-times' : 'pi pi-check'"></i>
                                </span>
                            </div>
                        </template>
                    </Card>
                </div>

                <div v-for="metric in metricCards" :key="metric.label" class="col-12 col-sm-6 col-xl-3">
                    <Card
                        class="metric-card h-100 border-0 shadow-sm"
                        :class="{ 'metric-clickable': canAccess(metric.permission) }"
                        @click="openMetric(metric)"
                    >
                        <template #content>
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="min-w-0">
                                    <span class="d-block small text-muted mb-2">{{ metric.label }}</span>
                                    <strong class="d-block fs-3 text-body">{{ metric.value }}</strong>
                                </div>
                                <span class="metric-icon" :class="`metric-icon-${metric.tone}`">
                                    <i :class="metric.icon"></i>
                                </span>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-xl-8">
                    <Card class="subscription-card h-100 border-0 shadow-sm">
                        <template #title>
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <span class="fs-6 fw-semibold">Crescimento das assinaturas</span>
                                    <small class="d-block text-muted fw-normal mt-1">Assinaturas vigentes nos últimos 12 meses</small>
                                </div>
                                <span class="rounded-pill bg-primary-subtle text-primary px-3 py-2 fs-6">
                                    {{ summary.active_subscriptions ?? 0 }} ativas
                                </span>
                            </div>
                        </template>
                        <template #content>
                            <div class="subscription-chart">
                                <Chart
                                    class="h-100"
                                    type="line"
                                    :data="chartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-12 col-xl-4">
                    <Card class="h-100 border-0 shadow-sm">
                        <template #title>
                            <span class="fs-6 fw-semibold">Saúde da rede</span>
                            <small class="d-block text-muted fw-normal mt-1">Comunicação atual dos pontos</small>
                        </template>
                        <template #content>
                            <div class="text-center py-3">
                                <div class="network-score mx-auto mb-3">
                                    <strong>{{ dashboard.network_health.online_percentage }}%</strong>
                                    <small>online</small>
                                </div>
                                <ProgressBar
                                    :value="dashboard.network_health.online_percentage"
                                    :showValue="false"
                                    class="mb-4"
                                />
                            </div>

                            <div class="d-grid gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="pi pi-circle-fill text-success me-2 network-dot"></i>Online</span>
                                    <strong>{{ dashboard.network_health.online }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="pi pi-circle-fill text-danger me-2 network-dot"></i>Sem comunicação</span>
                                    <strong>{{ dashboard.network_health.offline }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="pi pi-circle-fill text-secondary me-2 network-dot"></i>Sem player vinculado</span>
                                    <strong>{{ dashboard.network_health.without_player }}</strong>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-7">
                    <Card class="h-100 border-0 shadow-sm">
                        <template #title>
                            <div class="attention-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                                <div>
                                    <span class="fs-6 fw-semibold">Pontos que precisam de atenção</span>
                                    <small class="d-block text-muted fw-normal mt-1">Prioridade para manter a rede disponível</small>
                                </div>
                                <Button
                                    v-if="canAccess('display-points.view')"
                                    label="Ver todos"
                                    icon="pi pi-arrow-right"
                                    iconPos="right"
                                    text
                                    size="small"
                                    class="align-self-end align-self-sm-center"
                                    @click="router.push({ name: 'platform.display-points' })"
                                />
                            </div>
                        </template>
                        <template #content>
                            <div v-if="dashboard.attention_points.length" class="attention-list d-grid gap-2">
                                <div
                                    v-for="point in dashboard.attention_points"
                                    :key="point.id"
                                    class="attention-item d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3 rounded-3 p-3"
                                >
                                    <div class="attention-main d-flex align-items-start gap-3 min-w-0">
                                        <span class="attention-icon flex-shrink-0"><i class="pi pi-map-marker"></i></span>
                                        <div class="attention-details min-w-0">
                                            <strong class="d-block text-truncate">{{ point.name }}</strong>
                                            <small class="d-block text-muted text-truncate">
                                                {{ point.establishment ?? "Sem estabelecimento" }}
                                                <template v-if="point.location"> · {{ point.location }}</template>
                                            </small>
                                            <small v-if="point.player" class="d-block text-muted text-truncate mt-1">
                                                {{ point.player }} · {{ dateTime(point.last_seen_at) }}
                                            </small>
                                        </div>
                                    </div>
                                    <Tag
                                        class="align-self-start align-self-sm-center flex-shrink-0"
                                        :value="attentionLabel(point.reason)"
                                        :severity="attentionSeverity(point.reason)"
                                    />
                                </div>
                            </div>

                            <div v-else class="text-center py-5">
                                <span class="success-empty-icon d-inline-grid rounded-circle mb-3"><i class="pi pi-check"></i></span>
                                <strong class="d-block">Tudo funcionando por aqui</strong>
                                <small class="text-muted">Nenhum ponto precisa de atenção no momento.</small>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-12 col-xl-5">
                    <Card class="h-100 border-0 shadow-sm">
                        <template #title>
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <span class="fs-6 fw-semibold">Recebimentos recentes</span>
                                    <small class="d-block text-muted fw-normal mt-1">Últimas transações confirmadas</small>
                                </div>
                                <Button
                                    v-if="canAccess('transactions.view')"
                                    icon="pi pi-arrow-right"
                                    rounded
                                    text
                                    aria-label="Ver transações"
                                    @click="router.push({ name: 'platform.transactions' })"
                                />
                            </div>
                        </template>
                        <template #content>
                            <div v-if="dashboard.recent_transactions.length" class="d-grid">
                                <div
                                    v-for="transaction in dashboard.recent_transactions"
                                    :key="transaction.id"
                                    class="transaction-item d-flex justify-content-between align-items-center gap-3 py-3"
                                >
                                    <div class="d-flex align-items-center gap-3 min-w-0">
                                        <span class="transaction-icon flex-shrink-0"><i class="pi pi-arrow-down"></i></span>
                                        <div class="min-w-0">
                                            <strong class="d-block text-truncate">{{ transaction.customer ?? "Cliente não informado" }}</strong>
                                            <small class="d-block text-muted text-truncate">
                                                {{ paymentMethod(transaction.payment_method) }} · {{ dateTime(transaction.processed_at) }}
                                            </small>
                                        </div>
                                    </div>
                                    <strong class="text-success text-nowrap">+ {{ currency(transaction.amount) }}</strong>
                                </div>
                            </div>

                            <div v-else class="text-center py-5">
                                <i class="pi pi-receipt fs-2 text-muted"></i>
                                <strong class="d-block mt-3">Nenhum recebimento ainda</strong>
                                <small class="text-muted">As transações pagas aparecerão aqui.</small>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <small v-if="dashboard.generated_at" class="d-block text-muted text-end mt-3">
                Dados atualizados em {{ dateTime(dashboard.generated_at) }}
            </small>
        </template>

        <Card v-else-if="loadFailed" class="border-0 shadow-sm">
            <template #content>
                <div class="text-center py-5">
                    <span class="error-empty-icon d-inline-grid rounded-circle mb-3">
                        <i class="pi pi-exclamation-triangle"></i>
                    </span>
                    <strong class="d-block">Não foi possível carregar a dashboard</strong>
                    <small class="d-block text-muted mt-1 mb-3">Verifique a conexão e tente novamente.</small>
                    <Button label="Tentar novamente" icon="pi pi-refresh" outlined @click="fetchDashboard()" />
                </div>
            </template>
        </Card>
    </section>
</template>

<style scoped>
.min-w-0 {
    min-width: 0;
}

.metric-card,
.revenue-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.metric-clickable {
    cursor: pointer;
}

.metric-clickable:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.1) !important;
}

.revenue-card {
    background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%);
}

.revenue-icon {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.16);
}

.pending-subscription-card {
    color: var(--p-text-color);
    background: color-mix(in srgb, var(--p-orange-500) 8%, var(--p-content-background));
    box-shadow: 0 0.5rem 1.25rem color-mix(in srgb, var(--p-orange-500) 10%, transparent);
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.pending-subscription-card small {
    color: var(--p-text-muted-color);
}

.pending-subscription-card.has-pending {
    color: #ffffff;
    background: linear-gradient(135deg, #c2410c 0%, #f59e0b 100%);
    box-shadow: 0 0.75rem 1.75rem rgba(194, 65, 12, 0.24);
}

.pending-subscription-card.has-pending small {
    color: rgba(255, 255, 255, 0.82);
}

.pending-subscription-icon {
    color: #c2410c;
    background: color-mix(in srgb, var(--p-orange-500) 18%, transparent);
}

.has-pending .pending-subscription-icon {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.18);
}

.expired-subscription-card {
    color: var(--p-text-color);
    background: color-mix(in srgb, var(--p-red-500) 7%, var(--p-content-background));
    box-shadow: 0 0.5rem 1.25rem color-mix(in srgb, var(--p-red-500) 9%, transparent);
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.expired-subscription-card small {
    color: var(--p-text-muted-color);
}

.expired-subscription-card.has-expired {
    color: #ffffff;
    background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
    box-shadow: 0 0.75rem 1.75rem rgba(185, 28, 28, 0.22);
}

.expired-subscription-card.has-expired small {
    color: rgba(255, 255, 255, 0.82);
}

.expired-subscription-icon {
    color: #dc2626;
    background: color-mix(in srgb, var(--p-red-500) 16%, transparent);
}

.has-expired .expired-subscription-icon {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.18);
}

.metric-icon,
.attention-icon,
.transaction-icon {
    display: inline-grid;
    place-items: center;
}

.metric-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.85rem;
    font-size: 1.2rem;
}

.metric-icon-primary {
    color: #7c3aed;
    background: rgba(124, 58, 237, 0.12);
}

.metric-icon-success {
    color: #16a34a;
    background: rgba(34, 197, 94, 0.12);
}

.metric-icon-info {
    color: #0284c7;
    background: rgba(14, 165, 233, 0.12);
}

.metric-icon-warning {
    color: #d97706;
    background: rgba(245, 158, 11, 0.14);
}

.metric-icon-danger {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.12);
}

.subscription-chart {
    flex: 1 1 auto;
    width: 100%;
    min-height: 19rem;
}

.subscription-card :deep(.p-card-body) {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.subscription-card :deep(.p-card-content) {
    display: flex;
    flex: 1 1 auto;
    min-height: 0;
}

.subscription-chart :deep(.p-chart) {
    width: 100%;
    height: 100%;
}

.network-score {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 8rem;
    height: 8rem;
    border: 0.7rem solid rgba(34, 197, 94, 0.15);
    border-radius: 50%;
    color: #16a34a;
}

.network-score strong,
.network-score small {
    display: block;
    line-height: 1;
}

.network-score strong {
    font-size: 1.65rem;
}

.network-score small {
    margin-top: 0.35rem;
}

.network-dot {
    font-size: 0.55rem;
}

.attention-item {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    box-sizing: border-box;
    border: 1px solid var(--p-content-border-color);
    background: var(--p-content-background);
}

.attention-list {
    min-width: 0;
    grid-template-columns: minmax(0, 1fr);
}

.attention-main {
    flex: 1 1 auto;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
}

.attention-details {
    flex: 1 1 auto;
    max-width: 100%;
    overflow: hidden;
}

.attention-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    color: #dc2626;
    background: rgba(239, 68, 68, 0.1);
}

.transaction-item + .transaction-item {
    border-top: 1px solid var(--p-content-border-color);
}

.transaction-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    color: #16a34a;
    background: rgba(34, 197, 94, 0.12);
}

.success-empty-icon {
    place-items: center;
    width: 3rem;
    height: 3rem;
    color: #16a34a;
    background: rgba(34, 197, 94, 0.12);
}

.error-empty-icon {
    place-items: center;
    width: 3rem;
    height: 3rem;
    color: #dc2626;
    background: rgba(239, 68, 68, 0.12);
}

@media (max-width: 575.98px) {
    .subscription-chart {
        min-height: 16rem;
    }
}
</style>
