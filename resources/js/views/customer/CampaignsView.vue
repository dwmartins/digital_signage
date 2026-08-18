<script setup>
import AlertBox from "@/components/shared/AlertBox.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import { showAlert } from "@/helpers/alert";
import { formatDate } from "@/helpers/date";
import customerCampaignService from "@/services/customer-campaign-management.service";
import Skeleton from "primevue/skeleton";
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const loading = ref(true);
const campaigns = ref([]);
const categories = ref([]);
const summary = ref({});
const pagination = ref({});
const currentPage = ref(1);
const perPage = ref(6);
const filtersVisible = ref(false);

const filters = reactive({
    global: null,
    status: null,
    subscription_status: null,
    category_id: null,
});

const statusOptions = [
    { label: "Ativa", value: "active", icon: "pi pi-play-circle", description: "Campanha liberada para exibição quando os demais requisitos estiverem válidos." },
    { label: "Pendente", value: "pending", icon: "pi pi-clock", description: "Aguardando a aprovação da assinatura vinculada." },
    { label: "Pausada", value: "paused", icon: "pi pi-pause-circle", description: "Pausada pelo anunciante ou devido ao vencimento da assinatura." },
    { label: "Cancelada", value: "cancelled", icon: "pi pi-ban", description: "Campanha encerrada junto com a assinatura." },
];

const subscriptionStatusOptions = [
    { label: "Ativa", value: "active", icon: "pi pi-check-circle", description: "Contratação vigente e liberada." },
    { label: "Pendente", value: "pending", icon: "pi pi-clock", description: "Aguardando análise e confirmação do pagamento." },
    { label: "Vencida", value: "expired", icon: "pi pi-calendar-times", description: "Período contratado encerrado." },
    { label: "Cancelada", value: "cancelled", icon: "pi pi-ban", description: "Contratação encerrada." },
];

const summaryCards = computed(() => [
    { label: "Total de campanhas", value: summary.value.total ?? 0, icon: "pi pi-megaphone", tone: "primary" },
    { label: "Campanhas ativas", value: summary.value.active ?? 0, icon: "pi pi-play-circle", tone: "success" },
    { label: "Campanhas pendentes", value: summary.value.pending ?? 0, icon: "pi pi-clock", tone: "warning" },
    { label: "Campanhas pausadas", value: summary.value.paused ?? 0, icon: "pi pi-pause-circle", tone: "secondary" },
    { label: "Campanhas canceladas", value: summary.value.cancelled ?? 0, icon: "pi pi-ban", tone: "danger" },
    { label: "Mídias em análise", value: summary.value.pending_media ?? 0, icon: "pi pi-clock", tone: "warning" },
]);

const hasFilters = computed(() => Object.values(filters).some((value) => value !== null && value !== ""));

const campaignStatus = (status) => ({
    active: { label: "Ativa", severity: "success", icon: "pi pi-play-circle" },
    pending: { label: "Pendente", severity: "warn", icon: "pi pi-clock" },
    paused: { label: "Pausada", severity: "secondary", icon: "pi pi-pause-circle" },
    cancelled: { label: "Cancelada", severity: "danger", icon: "pi pi-ban" },
})[status] ?? { label: status, severity: "secondary" };

const subscriptionStatus = (status) => ({
    pending: { label: "Assinatura pendente", severity: "warn" },
    active: { label: "Assinatura ativa", severity: "success" },
    expired: { label: "Assinatura vencida", severity: "danger" },
    cancelled: { label: "Assinatura cancelada", severity: "secondary" },
})[status] ?? { label: "Sem assinatura", severity: "secondary" };

const mediaProgress = (campaign) => campaign.media_assets_count
    ? Math.round((campaign.approved_media_count / campaign.media_assets_count) * 100)
    : 0;

const distributionProgress = (campaign) => campaign.distribution_count
    ? Math.round((campaign.displayed_distribution_count / campaign.distribution_count) * 100)
    : 0;

const fetchOptions = async () => {
    const response = await customerCampaignService.options();
    categories.value = response.categories ?? [];
};

const fetchCampaigns = async (page = 1) => {
    try {
        loading.value = true;
        const response = await customerCampaignService.index(page, perPage.value, filters);
        campaigns.value = response.data ?? [];
        summary.value = response.summary ?? {};
        pagination.value = response.pagination ?? {};
        currentPage.value = response.pagination?.current_page ?? 1;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const applyFilters = () => {
    filtersVisible.value = false;
    fetchCampaigns(1);
};

const clearFilters = () => {
    Object.keys(filters).forEach((key) => (filters[key] = null));
    filtersVisible.value = false;
    fetchCampaigns(1);
};

const changePage = ({ page, rows }) => {
    perPage.value = rows;
    fetchCampaigns(page + 1);
};

onMounted(async () => {
    try {
        await fetchOptions();
    } catch (error) {
        showAlert("error", error.response?.data);
    }
    fetchCampaigns();
});
</script>

<template>
    <section class="container customer-campaigns py-3 py-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <Breadcrumb :items="[
                    { icon: 'pi pi-home', to: '/painel/inicio' },
                    { label: 'Minhas campanhas' },
                ]" />
                <div class="mt-3">
                    <h1 class="h3 fw-bold mb-1">Minhas campanhas</h1>
                    <p class="text-muted mb-0">Acompanhe a aprovação e a exibição dos seus conteúdos.</p>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <Button
                    class="d-lg-none"
                    label="Filtros"
                    icon="pi pi-filter"
                    outlined
                    @click="filtersVisible = true"
                />
                <Button
                    label="Nova campanha"
                    icon="pi pi-plus"
                    @click="router.push({ name: 'customer.campaign-onboarding' })"
                />
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div v-for="card in summaryCards" :key="card.label" class="col-6 col-xl-3">
                <Card class="summary-card h-100 border-0 shadow-sm">
                    <template #content>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="min-w-0">
                                <span class="d-block small text-muted text-truncate mb-1">{{ card.label }}</span>
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

        <Card class="filters-card d-none d-lg-block border-0 shadow-sm mb-4">
            <template #content>
                <form class="row g-3 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-xl-3">
                        <div class="field">
                            <label>Buscar campanha</label>
                            <InputText v-model="filters.global" placeholder="Nome, descrição ou plano" fluid />
                        </div>
                    </div>
                    <div class="col-xl-2">
                        <div class="field">
                            <label>Status da campanha</label>
                            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear fluid>
                                <template #option="{ option }">
                                    <div class="d-flex gap-2 py-1">
                                        <i :class="option.icon" class="text-primary mt-1"></i>
                                        <div><strong class="d-block">{{ option.label }}</strong><small class="text-muted">{{ option.description }}</small></div>
                                    </div>
                                </template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-xl-2">
                        <div class="field">
                            <label>Situação da assinatura</label>
                            <Select v-model="filters.subscription_status" :options="subscriptionStatusOptions" optionLabel="label" optionValue="value" showClear fluid>
                                <template #option="{ option }">
                                    <div class="d-flex gap-2 py-1">
                                        <i :class="option.icon" class="text-primary mt-1"></i>
                                        <div><strong class="d-block">{{ option.label }}</strong><small class="text-muted">{{ option.description }}</small></div>
                                    </div>
                                </template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-xl-2">
                        <div class="field">
                            <label>Categoria</label>
                            <Select v-model="filters.category_id" :options="categories" optionLabel="name" optionValue="id" filter showClear fluid>
                                <template #option="{ option }">
                                    <div><strong class="d-block">{{ option.name }}</strong><small class="text-muted">{{ option.campaigns_count }} campanha(s) nesta categoria</small></div>
                                </template>
                            </Select>
                        </div>
                    </div>
                    <div class="col-xl-3 d-flex justify-content-end gap-2">
                        <Button type="button" icon="pi pi-filter-slash" severity="secondary" outlined :disabled="!hasFilters" v-tooltip.top="'Limpar filtros'" @click="clearFilters" />
                        <Button type="submit" label="Buscar" icon="pi pi-search" />
                    </div>
                </form>
            </template>
        </Card>

        <AlertBox v-if="!loading && (summary.pending_media ?? 0) > 0" type="warning" class="mb-4">
            <strong>{{ summary.pending_media }} mídia(s) aguardam análise.</strong>
            A campanha pode permanecer ativa, mas somente conteúdos aprovados e com assinatura vigente serão exibidos.
        </AlertBox>

        <div v-if="loading" class="row g-3">
            <div v-for="index in 4" :key="index" class="col-12 col-xl-6">
                <Card class="campaign-card h-100 border-0 shadow-sm">
                    <template #content>
                        <div class="d-flex justify-content-between gap-3 mb-4"><div class="flex-grow-1"><Skeleton width="55%" height="1.2rem" class="mb-2" /><Skeleton width="35%" /></div><Skeleton width="5rem" height="1.5rem" /></div>
                        <Skeleton width="100%" height="7rem" />
                    </template>
                </Card>
            </div>
        </div>

        <div v-else-if="campaigns.length" class="row g-3">
            <div v-for="campaign in campaigns" :key="campaign.id" class="col-12 col-xl-6">
                <Card class="campaign-card h-100 border-0 shadow-sm">
                    <template #content>
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div class="min-w-0">
                                <small class="text-primary fw-semibold">CAMPANHA #{{ campaign.id }}</small>
                                <h2 class="h5 fw-bold text-truncate mt-1 mb-1">{{ campaign.name }}</h2>
                                <span class="text-muted small">{{ campaign.subscription?.plan?.name ?? "Plano não informado" }}</span>
                            </div>
                            <Tag :value="campaignStatus(campaign.status).label" :severity="campaignStatus(campaign.status).severity" :icon="campaignStatus(campaign.status).icon" />
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <Tag :value="subscriptionStatus(campaign.subscription?.status).label" :severity="subscriptionStatus(campaign.subscription?.status).severity" />
                            <Tag v-for="category in campaign.categories" :key="category.id" :value="category.name" severity="secondary" />
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6"><div class="info-tile"><i class="pi pi-images"></i><div><small>Mídias</small><strong>{{ campaign.media_assets_count }}/{{ campaign.subscription?.media_limit ?? "-" }}</strong></div></div></div>
                            <div class="col-6"><div class="info-tile"><i class="pi pi-map-marker"></i><div><small>Pontos</small><strong>{{ campaign.display_points_count }}/{{ campaign.subscription?.screen_limit ?? "-" }}</strong></div></div></div>
                            <div class="col-6"><div class="info-tile"><i class="pi pi-calendar"></i><div><small>Início</small><strong>{{ formatDate(campaign.subscription?.starts_at) }}</strong></div></div></div>
                            <div class="col-6"><div class="info-tile"><i class="pi pi-calendar-times"></i><div><small>Término</small><strong>{{ formatDate(campaign.subscription?.ends_at) }}</strong></div></div></div>
                        </div>

                        <div class="progress-section mb-3">
                            <div class="d-flex justify-content-between small mb-1"><span>Aprovação das mídias</span><strong>{{ campaign.approved_media_count }}/{{ campaign.media_assets_count }}</strong></div>
                            <ProgressBar :value="mediaProgress(campaign)" :showValue="false" style="height: 0.45rem" />
                        </div>
                        <div class="progress-section mb-4">
                            <div class="d-flex justify-content-between small mb-1"><span>Distribuição nos pontos</span><strong>{{ campaign.displayed_distribution_count }}/{{ campaign.distribution_count }}</strong></div>
                            <ProgressBar :value="distributionProgress(campaign)" :showValue="false" style="height: 0.45rem" />
                            <small v-if="campaign.failed_distribution_count" class="d-block text-danger mt-2"><i class="pi pi-exclamation-circle me-1"></i>{{ campaign.failed_distribution_count }} envio(s) com falha</small>
                        </div>

                        <Button label="Ver detalhes da campanha" icon="pi pi-arrow-right" iconPos="right" outlined fluid @click="router.push({ name: 'customer.campaigns.show', params: { id: campaign.id } })" />
                    </template>
                </Card>
            </div>
        </div>

        <div v-else class="empty-state text-center py-5 px-3">
            <span class="empty-icon d-inline-grid rounded-circle mb-3"><i class="pi pi-megaphone"></i></span>
            <h2 class="h5 fw-bold">{{ hasFilters ? "Nenhuma campanha encontrada" : "Crie sua primeira campanha" }}</h2>
            <p class="text-muted mb-3">{{ hasFilters ? "Revise os filtros aplicados para encontrar suas campanhas." : "Escolha um plano, os pontos de exibição e envie seus conteúdos." }}</p>
            <Button v-if="hasFilters" label="Limpar filtros" icon="pi pi-filter-slash" outlined @click="clearFilters" />
            <Button v-else label="Começar agora" icon="pi pi-arrow-right" iconPos="right" @click="router.push({ name: 'customer.campaign-onboarding' })" />
        </div>

        <Paginator v-if="!loading && pagination.total > perPage" class="mt-4" :first="(currentPage - 1) * perPage" :rows="perPage" :totalRecords="pagination.total" :rowsPerPageOptions="[6, 12, 18]" @page="changePage" />

        <Dialog v-model:visible="filtersVisible" modal header="Filtrar campanhas" :style="{ width: '94vw', maxWidth: '32rem' }" :draggable="false">
            <form class="d-flex flex-column gap-3" @submit.prevent="applyFilters">
                <div class="field"><label>Buscar</label><InputText v-model="filters.global" placeholder="Nome, descrição ou plano" fluid /></div>
                <div class="field">
                    <label>Status da campanha</label>
                    <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear fluid>
                        <template #option="{ option }">
                            <div class="d-flex gap-2 py-1">
                                <i :class="option.icon" class="text-primary mt-1"></i>
                                <div>
                                    <strong class="d-block">{{ option.label }}</strong>
                                    <small class="text-muted">{{ option.description }}</small>
                                </div>
                            </div>
                        </template>
                    </Select>
                </div>
                <div class="field">
                    <label>Situação da assinatura</label>
                    <Select v-model="filters.subscription_status" :options="subscriptionStatusOptions" optionLabel="label" optionValue="value" showClear fluid>
                        <template #option="{ option }">
                            <div class="d-flex gap-2 py-1">
                                <i :class="option.icon" class="text-primary mt-1"></i>
                                <div>
                                    <strong class="d-block">{{ option.label }}</strong>
                                    <small class="text-muted">{{ option.description }}</small>
                                </div>
                            </div>
                        </template>
                    </Select>
                </div>
                <div class="field">
                    <label>Categoria</label>
                    <Select v-model="filters.category_id" :options="categories" optionLabel="name" optionValue="id" filter showClear fluid>
                        <template #option="{ option }">
                            <div>
                                <strong class="d-block">{{ option.name }}</strong>
                                <small class="text-muted">{{ option.campaigns_count }} campanha(s) nesta categoria</small>
                            </div>
                        </template>
                    </Select>
                </div>
            </form>
            <template #footer><Button label="Limpar" severity="secondary" outlined :disabled="!hasFilters" @click="clearFilters" /><Button label="Aplicar filtros" icon="pi pi-search" @click="applyFilters" /></template>
        </Dialog>
    </section>
</template>

<style scoped>
.customer-campaigns {
    min-height: calc(100vh - 5rem);
}

.min-w-0 {
    min-width: 0;
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

.summary-icon-secondary {
    color: #64748b;
    background: rgba(100, 116, 139, 0.12);
}

.summary-icon-warning {
    color: #d97706;
    background: rgba(245, 158, 11, 0.14);
}

.summary-icon-danger {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.12);
}

.campaign-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.campaign-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.9rem 1.8rem rgba(15, 23, 42, 0.08) !important;
}

.info-tile {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    height: 100%;
    padding: 0.75rem;
    border-radius: 0.7rem;
    background: var(--p-surface-50);
}

.info-tile i {
    color: var(--p-primary-color);
}

.info-tile small,
.info-tile strong {
    display: block;
}

.info-tile small {
    color: var(--p-text-muted-color);
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
    font-size: 1.4rem;
}
</style>
