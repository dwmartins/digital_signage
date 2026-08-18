<script setup>
import AlertBox from "@/components/shared/AlertBox.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import Spinner from "@/components/shared/Spinner.vue";
import { showAlert } from "@/helpers/alert";
import { formatDate } from "@/helpers/date";
import customerCampaignService from "@/services/customer-campaign-management.service";
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const savingStatus = ref(false);
const campaign = ref(null);
const statusDialogVisible = ref(false);

const nextStatus = computed(() => campaign.value?.status === "active" ? "paused" : "active");
const isSubscriptionActive = computed(() => campaign.value?.subscription?.status === "active");
const canManageStatus = computed(() =>
    isSubscriptionActive.value
    && ["active", "paused"].includes(campaign.value?.status),
);

const campaignStatus = (status) => ({
    active: { label: "Ativa", severity: "success", icon: "pi pi-play-circle" },
    pending: { label: "Pendente", severity: "warn", icon: "pi pi-clock" },
    paused: { label: "Pausada", severity: "secondary", icon: "pi pi-pause-circle" },
    cancelled: { label: "Cancelada", severity: "danger", icon: "pi pi-ban" },
})[status] ?? { label: status, severity: "secondary" };

const subscriptionStatus = (status) => ({
    pending: { label: "Pendente", severity: "warn" },
    active: { label: "Ativa", severity: "success" },
    expired: { label: "Vencida", severity: "danger" },
    cancelled: { label: "Cancelada", severity: "secondary" },
})[status] ?? { label: "Não informada", severity: "secondary" };

const approvalStatus = (status) => ({
    pending_approval: { label: "Aguardando aprovação", severity: "warn" },
    awaiting_subscription: { label: "Aguardando assinatura", severity: "warn" },
    approved: { label: "Aprovada", severity: "success" },
    rejected: { label: "Rejeitada", severity: "danger" },
})[status] ?? { label: status, severity: "secondary" };

const processingStatus = (status) => ({
    processing: { label: "Processando", severity: "info" },
    ready: { label: "Arquivo pronto", severity: "success" },
    failed: { label: "Falha no processamento", severity: "danger" },
})[status] ?? { label: status, severity: "secondary" };

const distributionSummary = (mediaId) => {
    const distributions = campaign.value?.media_distributions?.filter(
        (distribution) => distribution.media_asset_id === mediaId,
    ) ?? [];
    const displayed = distributions.filter((distribution) => distribution.status === "displayed").length;
    const failed = distributions.filter((distribution) => distribution.status === "failed").length;

    if (failed) return { label: `${failed} envio(s) com falha`, severity: "danger", icon: "pi pi-times-circle" };
    if (!distributions.length) return { label: "Sem ponto vinculado", severity: "secondary", icon: "pi pi-minus-circle" };
    if (displayed === distributions.length) return { label: `Em exibição em ${displayed} ponto(s)`, severity: "success", icon: "pi pi-play-circle" };

    return { label: `${displayed}/${distributions.length} ponto(s) em exibição`, severity: "warn", icon: "pi pi-clock" };
};

const money = (value) => Number(value ?? 0).toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
});

const formatSize = (bytes) => {
    if (!bytes) return "-";
    return bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.ceil(bytes / 1024)} KB`;
};

const address = (establishment) => [
    establishment?.address,
    establishment?.number,
    establishment?.neighborhood?.name,
    establishment?.city?.name,
    establishment?.city?.state?.code,
].filter(Boolean).join(" · ") || "Endereço não informado";

const fetchCampaign = async () => {
    try {
        loading.value = true;
        const response = await customerCampaignService.show(route.params.id);
        campaign.value = response.campaign;
    } catch (error) {
        showAlert("error", error.response?.data);
        router.push({ name: "customer.campaigns" });
    } finally {
        loading.value = false;
    }
};

const updateStatus = async () => {
    try {
        savingStatus.value = true;
        const response = await customerCampaignService.updateStatus(campaign.value.id, nextStatus.value);
        campaign.value.status = response.campaign.status;
        statusDialogVisible.value = false;
        showAlert("success", response.message);
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        savingStatus.value = false;
    }
};

onMounted(fetchCampaign);
</script>

<template>
    <section class="container campaign-details py-3 py-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <Breadcrumb :items="[
                    { icon: 'pi pi-home', to: '/painel/inicio' },
                    { label: 'Minhas campanhas', to: '/painel/campanhas' },
                    { label: campaign?.name ?? 'Detalhes' },
                ]" />
            </div>
            <Button label="Voltar" icon="pi pi-arrow-left" severity="secondary" outlined @click="router.push({ name: 'customer.campaigns' })" />
        </div>

        <div v-if="loading" class="d-flex justify-content-center align-items-center loading-state">
            <Spinner />
        </div>

        <template v-else-if="campaign">
            <Card class="hero-card border-0 shadow-sm mb-4">
                <template #content>
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-4">
                        <div class="min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <small class="text-primary fw-semibold">CAMPANHA #{{ campaign.id }}</small>
                                <Tag :value="campaignStatus(campaign.status).label" :severity="campaignStatus(campaign.status).severity" :icon="campaignStatus(campaign.status).icon" />
                            </div>
                            <h1 class="h3 fw-bold mb-2">{{ campaign.name }}</h1>
                            <p class="text-muted mb-3">{{ campaign.description || "Nenhuma descrição foi adicionada a esta campanha." }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <Tag v-for="category in campaign.categories" :key="category.id" :value="category.name" severity="secondary" />
                                <Tag :value="campaign.playback_mode === 'random' ? 'Exibição aleatória' : 'Ordem definida por você'" :icon="campaign.playback_mode === 'random' ? 'pi pi-shuffle' : 'pi pi-list'" severity="info" />
                            </div>
                        </div>
                        <Button
                            v-if="canManageStatus"
                            :label="campaign.status === 'active' ? 'Pausar campanha' : 'Ativar campanha'"
                            :icon="campaign.status === 'active' ? 'pi pi-pause' : 'pi pi-play'"
                            :severity="campaign.status === 'active' ? 'secondary' : 'success'"
                            @click="statusDialogVisible = true"
                        />
                    </div>
                </template>
            </Card>

            <AlertBox v-if="!isSubscriptionActive" type="warning" class="mb-4">
                <strong>A assinatura desta campanha não está ativa.</strong>
                Você pode organizar a campanha, mas ela somente será exibida quando a contratação estiver vigente e as mídias estiverem aprovadas.
            </AlertBox>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-5">
                    <Card class="h-100 border-0 shadow-sm">
                        <template #title>Plano e contratação</template>
                        <template #content>
                            <div class="plan-highlight rounded-3 p-3 mb-3">
                                <small class="d-block text-muted">Plano contratado</small>
                                <strong class="d-block fs-5">{{ campaign.subscription?.plan?.name ?? "Não informado" }}</strong>
                                <span class="text-primary fw-bold">{{ money(campaign.subscription?.price) }}</span>
                                <small class="text-muted"> / {{ campaign.subscription?.billing_cycle === "yearly" ? "anual" : "mensal" }}</small>
                            </div>
                            <div class="row g-3 detail-grid">
                                <div class="col-6"><small>Status</small><Tag :value="subscriptionStatus(campaign.subscription?.status).label" :severity="subscriptionStatus(campaign.subscription?.status).severity" /></div>
                                <div class="col-6"><small>Tipo de mídia</small><strong>{{ campaign.subscription?.media_type === "video" ? "Vídeos" : "Imagens" }}</strong></div>
                                <div class="col-6"><small>Data inicial</small><strong>{{ formatDate(campaign.subscription?.starts_at) }}</strong></div>
                                <div class="col-6"><small>Data final</small><strong>{{ formatDate(campaign.subscription?.ends_at) }}</strong></div>
                                <div class="col-6"><small>Limite de mídias</small><strong>{{ campaign.media_assets?.length ?? 0 }}/{{ campaign.subscription?.media_limit ?? "-" }}</strong></div>
                                <div class="col-6"><small>Limite de pontos</small><strong>{{ campaign.display_points?.length ?? 0 }}/{{ campaign.subscription?.screen_limit ?? "-" }}</strong></div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-12 col-xl-7">
                    <Card class="h-100 border-0 shadow-sm">
                        <template #title>Pontos de exibição</template>
                        <template #subtitle>Locais selecionados para apresentar esta campanha.</template>
                        <template #content>
                            <div v-if="campaign.display_points?.length" class="row g-3">
                                <div v-for="point in campaign.display_points" :key="point.id" class="col-12 col-md-6">
                                    <article class="point-card h-100 border rounded-3 p-3">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div class="d-flex gap-2 min-w-0"><span class="point-icon"><i class="pi pi-map-marker"></i></span><div class="min-w-0"><strong class="d-block text-truncate">{{ point.establishment?.name }}</strong><span class="d-block text-truncate">{{ point.name }}</span></div></div>
                                            <Tag :value="point.orientation === 'portrait' ? 'Vertical' : 'Horizontal'" :icon="point.orientation === 'portrait' ? 'pi pi-mobile' : 'pi pi-desktop'" severity="secondary" />
                                        </div>
                                        <small class="d-block text-muted mb-1">{{ point.location || "Local interno não informado" }}</small>
                                        <small class="d-block text-muted">{{ address(point.establishment) }}</small>
                                        <small class="d-block text-muted mt-2"><i class="pi pi-clock me-1"></i>{{ point.establishment?.opening_hours || "Horário não informado" }}</small>
                                    </article>
                                </div>
                            </div>
                            <div v-else class="text-center text-muted border rounded-3 p-4">Nenhum ponto de exibição foi vinculado.</div>
                        </template>
                    </Card>
                </div>
            </div>

            <Card class="border-0 shadow-sm">
                <template #title>Mídias da campanha</template>
                <template #subtitle>Acompanhe a aprovação e a disponibilidade de cada conteúdo.</template>
                <template #content>
                    <AlertBox type="info" class="mb-3">
                        A aprovação confirma que o conteúdo está adequado. O status de exibição mostra se o arquivo já foi distribuído aos pontos selecionados.
                    </AlertBox>
                    <div v-if="campaign.media_assets?.length" class="d-flex flex-column gap-3">
                        <article v-for="(media, index) in campaign.media_assets" :key="media.id" class="media-card d-flex flex-column flex-md-row align-items-md-center gap-3 border rounded-3 p-3">
                            <img v-if="media.type === 'image'" :src="media.preview_url" :alt="media.name" class="media-preview rounded-2" />
                            <div v-else class="media-preview video-preview rounded-2"><i class="pi pi-video"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <small class="text-primary fw-semibold">{{ index + 1 }}º CONTEÚDO</small>
                                <strong class="d-block text-truncate">{{ media.name }}</strong>
                                <small class="d-block text-muted text-truncate">{{ media.original_name }} · {{ formatSize(media.size_bytes) }}</small>
                                <small class="d-block text-muted mt-1">{{ media.width && media.height ? `${media.width} × ${media.height}px` : "Resolução não identificada" }}</small>
                            </div>
                            <div class="d-flex flex-column align-items-start align-items-md-end gap-2">
                                <Tag :value="approvalStatus(media.approval_status).label" :severity="approvalStatus(media.approval_status).severity" />
                                <Tag :value="processingStatus(media.processing_status).label" :severity="processingStatus(media.processing_status).severity" />
                                <Tag :value="distributionSummary(media.id).label" :severity="distributionSummary(media.id).severity" :icon="distributionSummary(media.id).icon" />
                            </div>
                        </article>
                    </div>
                    <div v-else class="text-center text-muted border rounded-3 p-4">Nenhuma mídia foi vinculada à campanha.</div>
                </template>
            </Card>
        </template>

        <Dialog v-model:visible="statusDialogVisible" modal :header="nextStatus === 'active' ? 'Ativar campanha' : 'Pausar campanha'" :style="{ width: '32rem' }" :breakpoints="{ '576px': '94vw' }" :draggable="false">
            <div class="d-flex gap-3">
                <span class="status-dialog-icon"><i :class="nextStatus === 'active' ? 'pi pi-play' : 'pi pi-pause'"></i></span>
                <div><strong class="d-block mb-2">{{ nextStatus === "active" ? "Deseja ativar esta campanha?" : "Deseja pausar esta campanha?" }}</strong><p class="text-muted mb-0">{{ nextStatus === "active" ? "Ela entrará na programação quando a assinatura, as mídias e a distribuição estiverem válidas." : "Os conteúdos deixarão de fazer parte da programação até uma nova ativação." }}</p></div>
            </div>
            <template #footer><Button label="Cancelar" severity="secondary" text :disabled="savingStatus" @click="statusDialogVisible = false" /><Button :label="nextStatus === 'active' ? 'Sim, ativar' : 'Sim, pausar'" :icon="nextStatus === 'active' ? 'pi pi-play' : 'pi pi-pause'" :severity="nextStatus === 'active' ? 'success' : 'secondary'" :loading="savingStatus" @click="updateStatus" /></template>
        </Dialog>
    </section>
</template>

<style scoped>
.campaign-details {
    min-height: calc(100vh - 5rem);
}

.loading-state {
    min-height: 55vh;
}

.min-w-0 {
    min-width: 0;
}

.hero-card {
    background:
        radial-gradient(
            circle at top right,
            color-mix(in srgb, var(--p-primary-color) 10%, transparent),
            transparent 42%
        ),
        var(--p-content-background);
}

.plan-highlight {
    background: color-mix(in srgb, var(--p-primary-color) 6%, var(--p-surface-50));
}

.detail-grid small,
.detail-grid strong {
    display: block;
}

.detail-grid small {
    margin-bottom: 0.25rem;
    color: var(--p-text-muted-color);
}

.point-card {
    border-color: var(--p-content-border-color) !important;
}

.point-icon,
.status-dialog-icon,
.video-preview {
    display: inline-grid;
    place-items: center;
}

.point-icon {
    width: 2.25rem;
    height: 2.25rem;
    flex: 0 0 auto;
    border-radius: 0.65rem;
    color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
}

.media-preview {
    width: 6rem;
    height: 4.5rem;
    flex: 0 0 6rem;
    object-fit: cover;
    background: var(--p-surface-100);
}

.video-preview {
    color: var(--p-primary-color);
    font-size: 1.7rem;
}

.status-dialog-icon {
    width: 3rem;
    height: 3rem;
    flex: 0 0 auto;
    border-radius: 50%;
    color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
}

@media (max-width: 767.98px) {
    .media-preview {
        width: 100%;
        height: 11rem;
    }
}
</style>
