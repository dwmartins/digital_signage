<script setup>
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import { showAlert } from "@/helpers/alert";
import { formatDate } from "@/helpers/date";
import customerSubscriptionService from "@/services/customer-subscription.service";
import Skeleton from "primevue/skeleton";
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const loading = ref(true);
const subscriptions = ref([]);
const summary = ref({});
const pagination = ref({});
const currentPage = ref(1);
const perPage = ref(6);
const status = ref(null);

const statusOptions = [
    { label: "Todas", value: null },
    { label: "Pendentes", value: "pending" },
    { label: "Ativas", value: "active" },
    { label: "Vencidas", value: "expired" },
    { label: "Canceladas", value: "cancelled" },
];

const summaryCards = computed(() => [
    { label: "Total", value: summary.value.total ?? 0, icon: "pi pi-file-check", tone: "primary" },
    { label: "Ativas", value: summary.value.active ?? 0, icon: "pi pi-check-circle", tone: "success" },
    { label: "Pendentes", value: summary.value.pending ?? 0, icon: "pi pi-clock", tone: "warning" },
    { label: "Vencidas", value: summary.value.expired ?? 0, icon: "pi pi-calendar-times", tone: "danger" },
]);

const statusDetails = (value) => ({
    pending: { label: "Pendente", severity: "warn" },
    active: { label: "Ativa", severity: "success" },
    expired: { label: "Vencida", severity: "danger" },
    cancelled: { label: "Cancelada", severity: "secondary" },
})[value] ?? { label: value, severity: "secondary" };

const money = (value) => Number(value ?? 0).toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
});

const cycle = (value) => value === "yearly" ? "Anual" : "Mensal";
const mediaType = (value) => value === "video" ? "Vídeos" : "Imagens";

const fetchSubscriptions = async (page = 1) => {
    try {
        loading.value = true;
        const response = await customerSubscriptionService.index(page, perPage.value, {
            status: status.value,
        });

        subscriptions.value = response.data ?? [];
        summary.value = response.summary ?? {};
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const changePage = ({ page, rows }) => {
    perPage.value = rows;
    fetchSubscriptions(page + 1);
};

onMounted(() => fetchSubscriptions());
</script>

<template>
    <section class="container customer-subscriptions py-3 py-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <Breadcrumb :items="[
                    { icon: 'pi pi-home', to: '/painel/inicio' },
                    { label: 'Minhas assinaturas' },
                ]" />
                <div class="mt-3">
                    <h1 class="h3 fw-bold mb-1">Minhas assinaturas</h1>
                    <p class="text-muted mb-0">Acompanhe seus planos, vigências e campanhas contratadas.</p>
                </div>
            </div>

            <Button
                label="Começar nova campanha"
                icon="pi pi-plus"
                @click="router.push({ name: 'customer.campaign-onboarding' })"
            />
        </div>

        <div class="row g-3 mb-4">
            <div v-for="card in summaryCards" :key="card.label" class="col-6 col-xl-3">
                <Card class="summary-card h-100 border-0 shadow-sm">
                    <template #content>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <span class="d-block small text-muted mb-1">{{ card.label }}</span>
                                <strong class="fs-4">{{ card.value }}</strong>
                            </div>
                            <span class="summary-icon" :class="`summary-icon-${card.tone}`">
                                <i :class="card.icon"></i>
                            </span>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <Card class="border-0 shadow-sm">
            <template #content>
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Histórico de contratações</h2>
                        <small class="text-muted">Consulte todas as suas assinaturas em um só lugar.</small>
                    </div>
                    <div class="filter-field">
                        <label for="subscription-status" class="d-block small fw-semibold text-muted mb-1">STATUS</label>
                        <Select
                            v-model="status"
                            inputId="subscription-status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            fluid
                            @change="fetchSubscriptions(1)"
                        />
                    </div>
                </div>

                <div v-if="loading" class="row g-3">
                    <div v-for="index in 3" :key="index" class="col-12 col-xl-6">
                        <div class="subscription-card border rounded-3 p-3">
                            <div class="d-flex justify-content-between gap-3 mb-4">
                                <div class="flex-grow-1">
                                    <Skeleton width="45%" height="1.2rem" class="mb-2" />
                                    <Skeleton width="70%" height="0.9rem" />
                                </div>
                                <Skeleton width="5rem" height="1.5rem" />
                            </div>
                            <Skeleton width="100%" height="5rem" />
                        </div>
                    </div>
                </div>

                <div v-else-if="subscriptions.length" class="row g-3">
                    <div v-for="item in subscriptions" :key="item.id" class="col-12 col-xl-6">
                        <article class="subscription-card h-100 border rounded-3 p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div class="min-w-0">
                                    <small class="text-primary fw-semibold">ASSINATURA #{{ item.id }}</small>
                                    <h3 class="h5 fw-bold text-truncate mt-1 mb-1">{{ item.plan?.name }}</h3>
                                    <span class="text-muted small">
                                        {{ item.campaign?.name ?? "Campanha ainda não cadastrada" }}
                                    </span>
                                </div>
                                <Tag
                                    :value="statusDetails(item.status).label"
                                    :severity="statusDetails(item.status).severity"
                                />
                            </div>

                            <div class="subscription-price d-flex justify-content-between align-items-end gap-3 rounded-3 p-3 mb-3">
                                <div>
                                    <small class="d-block text-muted">Valor contratado</small>
                                    <strong class="fs-4 text-primary">{{ money(item.price) }}</strong>
                                    <span class="small text-muted"> / {{ cycle(item.billing_cycle).toLowerCase() }}</span>
                                </div>
                                <span class="cycle-badge rounded-pill px-3 py-1">{{ cycle(item.billing_cycle) }}</span>
                            </div>

                            <div class="row g-3 subscription-details">
                                <div class="col-6">
                                    <small>Início</small>
                                    <strong>{{ formatDate(item.starts_at) }}</strong>
                                </div>
                                <div class="col-6">
                                    <small>Término</small>
                                    <strong>{{ formatDate(item.ends_at) }}</strong>
                                </div>
                                <div class="col-6">
                                    <small>Conteúdos</small>
                                    <strong>{{ item.media_limit }} {{ mediaType(item.media_type).toLowerCase() }}</strong>
                                </div>
                                <div class="col-6">
                                    <small>Pontos de exibição</small>
                                    <strong>Até {{ item.screen_limit }}</strong>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-else class="empty-state text-center py-5 px-3">
                    <span class="empty-icon d-inline-grid rounded-circle mb-3">
                        <i class="pi pi-file-check"></i>
                    </span>
                    <h3 class="h5 fw-bold">Nenhuma assinatura encontrada</h3>
                    <p class="text-muted mb-3">
                        {{ status ? "Não existem assinaturas com o status selecionado." : "Escolha um plano para começar sua primeira campanha." }}
                    </p>
                    <Button
                        v-if="!status"
                        label="Conhecer os planos"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        @click="router.push({ name: 'customer.campaign-onboarding' })"
                    />
                </div>

                <Paginator
                    v-if="!loading && pagination.total > perPage"
                    class="mt-4"
                    :first="(currentPage - 1) * perPage"
                    :rows="perPage"
                    :totalRecords="pagination.total"
                    :rowsPerPageOptions="[6, 12, 18]"
                    @page="changePage"
                />
            </template>
        </Card>
    </section>
</template>

<style scoped>
.customer-subscriptions {
    min-height: calc(100vh - 5rem);
}

.min-w-0 {
    min-width: 0;
}

.summary-card,
.subscription-card {
    background: var(--p-content-background);
}

.summary-icon,
.empty-icon {
    display: inline-grid;
    place-items: center;
}

.summary-icon {
    width: 2.8rem;
    height: 2.8rem;
    flex: 0 0 auto;
    border-radius: 0.8rem;
}

.summary-icon-primary {
    color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
}

.summary-icon-success {
    color: #16a34a;
    background: rgba(34, 197, 94, 0.12);
}

.summary-icon-warning {
    color: #d97706;
    background: rgba(245, 158, 11, 0.14);
}

.summary-icon-danger {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.12);
}

.filter-field {
    width: 14rem;
}

.subscription-card {
    border-color: var(--p-content-border-color) !important;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.subscription-card:hover {
    border-color: color-mix(in srgb, var(--p-primary-color) 40%, var(--p-content-border-color)) !important;
    box-shadow: 0 0.7rem 1.5rem rgba(15, 23, 42, 0.06);
}

.subscription-price {
    background: color-mix(in srgb, var(--p-primary-color) 5%, var(--p-surface-50));
}

.cycle-badge {
    color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
    font-size: 0.78rem;
    font-weight: 600;
}

.subscription-details small,
.subscription-details strong {
    display: block;
}

.subscription-details small {
    margin-bottom: 0.2rem;
    color: var(--p-text-muted-color);
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
    font-size: 1.4rem;
}

@media (max-width: 575.98px) {
    .filter-field {
        width: 100%;
    }
}
</style>
