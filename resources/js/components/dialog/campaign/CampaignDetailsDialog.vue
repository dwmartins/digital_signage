<script setup>
import Spinner from "@/components/shared/Spinner.vue";
import { formatDate } from "@/helpers/date";
import { showAlert } from "@/helpers/alert";
import campaignService from "@/services/campaign.service";
import { computed, ref, watch } from "vue";

const props = defineProps({
    modelValue: Boolean,
    campaignId: Number,
});

const emit = defineEmits(["update:modelValue"]);

const campaign = ref(null);
const loading = ref(false);

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const campaignStatus = (status) => ({
    active: { label: "Ativa", severity: "success" },
    inactive: { label: "Inativa", severity: "secondary" },
})[status] ?? { label: status, severity: "secondary" };

const subscriptionStatus = (status) => ({
    pending: { label: "Pendente", severity: "warn" },
    active: { label: "Ativa", severity: "success" },
    expired: { label: "Vencida", severity: "danger" },
    cancelled: { label: "Cancelada", severity: "secondary" },
})[status] ?? { label: status, severity: "secondary" };

const approvalStatus = (status) => ({
    pending_approval: { label: "Aguardando aprovação", severity: "warn" },
    awaiting_subscription: { label: "Aguardando assinatura", severity: "warn" },
    approved: { label: "Aprovada", severity: "success" },
    rejected: { label: "Rejeitada", severity: "danger" },
})[status] ?? { label: status, severity: "secondary" };

const distributionStatus = (status) => ({
    pending: {
        status: "pending",
        label: "Aguardando envio",
        severity: "warn",
        icon: "pi pi-clock",
    },
    processing: {
        status: "processing",
        label: "Processando envio",
        severity: "info",
        icon: "pi pi-sync",
    },
    displayed: {
        status: "displayed",
        label: "Em exibição",
        severity: "success",
        icon: "pi pi-play-circle",
    },
    failed: {
        status: "failed",
        label: "Falha no envio",
        severity: "danger",
        icon: "pi pi-times-circle",
    },
})[status] ?? {
    status,
    label: status,
    severity: "secondary",
    icon: "pi pi-question-circle",
};

const distributionSummary = (mediaId) => {
    const distributions = campaign.value?.media_distributions?.filter(
        (distribution) => distribution.media_asset_id === mediaId,
    ) ?? [];

    if (!distributions.length) {
        return {
            status: "without_display_point",
            label: "Sem ponto vinculado",
            severity: "secondary",
            icon: "pi pi-minus-circle",
        };
    }

    const displayed = distributions.filter(
        (distribution) => distribution.status === "displayed",
    ).length;

    if (distributions.some((distribution) => distribution.status === "failed")) {
        return distributionStatus("failed");
    }

    if (distributions.some((distribution) => distribution.status === "processing")) {
        return distributionStatus("processing");
    }

    if (displayed === distributions.length) {
        return {
            ...distributionStatus("displayed"),
            label: `Em exibição em ${displayed} ponto(s)`,
        };
    }

    if (displayed > 0) {
        return {
            ...distributionStatus("pending"),
            label: `${displayed}/${distributions.length} ponto(s) em exibição`,
        };
    }

    return distributionStatus("pending");
};

const mediaType = (type) => type === "video" ? "Vídeo" : "Imagem";

const formatSize = (bytes) => {
    if (!bytes) return "-";

    return bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.ceil(bytes / 1024)} KB`;
};

const formatAddress = (establishment) => {
    if (!establishment) return "Endereço não informado";

    const address = [
        establishment.address,
        establishment.number,
        establishment.neighborhood,
        establishment.city,
        establishment.state,
    ].filter(Boolean);

    return address.length ? address.join(" · ") : "Endereço não informado";
};

const fetchCampaign = async () => {
    if (!props.campaignId) return;

    try {
        loading.value = true;
        campaign.value = null;
        const response = await campaignService.show(props.campaignId);
        campaign.value = response.campaign;
    } catch (error) {
        showAlert("error", error.response?.data);
        visible.value = false;
    } finally {
        loading.value = false;
    }
};

watch(
    [() => props.modelValue, () => props.campaignId],
    ([opened]) => {
        if (opened) {
            fetchCampaign();
        } else {
            campaign.value = null;
        }
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Detalhes da campanha"
        :style="{ width: '64rem' }"
        :breakpoints="{ '992px': '96vw' }"
        :draggable="false"
    >
        <div v-if="loading" class="d-flex justify-content-center py-5">
            <Spinner />
        </div>

        <div v-else-if="campaign" class="d-flex flex-column gap-4">
            <section class="p-3 border rounded-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <small class="d-block text-muted">Campanha #{{ campaign.id }}</small>
                        <h4 class="mb-1">{{ campaign.name }}</h4>
                        <span class="text-muted">
                            {{ campaign.customer?.name }} {{ campaign.customer?.last_name }}
                        </span>
                    </div>
                    <Tag
                        :value="campaignStatus(campaign.status).label"
                        :severity="campaignStatus(campaign.status).severity"
                    />
                </div>
                <p v-if="campaign.description" class="mt-3 mb-0 text-muted">
                    {{ campaign.description }}
                </p>
            </section>

            <section>
                <h5 class="mb-3">Plano e assinatura</h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="h-100 p-3 border rounded-3">
                            <small class="d-block text-muted">Plano</small>
                            <strong>{{ campaign.subscription?.plan?.name || "Não informado" }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="h-100 p-3 border rounded-3">
                            <small class="d-block text-muted">Tipo de mídia</small>
                            <strong>{{ mediaType(campaign.subscription?.media_type) }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="h-100 p-3 border rounded-3">
                            <small class="d-block text-muted">Limite de telas</small>
                            <strong>{{ campaign.subscription?.screen_limit ?? "-" }}</strong>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="h-100 p-3 border rounded-3">
                            <small class="d-block text-muted">Assinatura</small>
                            <Tag
                                :value="subscriptionStatus(campaign.subscription?.status).label"
                                :severity="subscriptionStatus(campaign.subscription?.status).severity"
                            />
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="h-100 p-3 border rounded-3">
                            <small class="d-block text-muted">Início</small>
                            <strong>{{ formatDate(campaign.subscription?.starts_at) }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="h-100 p-3 border rounded-3">
                            <small class="d-block text-muted">Término</small>
                            <strong>{{ formatDate(campaign.subscription?.ends_at) }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="mb-0">Pontos de exibição</h5>
                    <Tag
                        :value="`${campaign.display_points?.length ?? 0} selecionado(s)`"
                        severity="secondary"
                    />
                </div>

                <div v-if="campaign.display_points?.length" class="row g-3">
                    <div
                        v-for="point in campaign.display_points"
                        :key="point.id"
                        class="col-12 col-md-6"
                    >
                        <article class="d-flex gap-3 h-100 p-3 border rounded-3">
                            <i class="pi pi-map-marker mt-1 text-primary"></i>
                            <div>
                                <strong class="d-block">{{ point.establishment?.name }}</strong>
                                <span class="d-block">{{ point.name }}</span>
                                <small class="d-block text-muted">
                                    {{ point.location || "Local interno não informado" }}
                                </small>
                                <small class="d-block mt-1 text-muted">
                                    {{ formatAddress(point.establishment) }}
                                </small>
                                <small class="d-block mt-1 text-muted">
                                    <i class="pi pi-clock me-1"></i>
                                    {{ point.establishment?.opening_hours || "Horário não informado" }}
                                </small>
                            </div>
                        </article>
                    </div>
                </div>
                <div v-else class="p-4 text-center text-muted border rounded-3">
                    Nenhum ponto de exibição vinculado.
                </div>
            </section>

            <section>
                <h5 class="mb-3">Mídias da campanha</h5>
                <div v-if="campaign.media_assets?.length" class="d-flex flex-column gap-3">
                    <article
                        v-for="media in campaign.media_assets"
                        :key="media.id"
                        class="d-flex align-items-center gap-3 p-3 border rounded-3 flex-wrap"
                    >
                        <img
                            v-if="media.type === 'image'"
                            :src="media.content_url"
                            :alt="media.name"
                            class="campaign-media-preview rounded-2"
                        />
                        <div
                            v-else
                            class="campaign-media-preview d-flex align-items-center justify-content-center rounded-2"
                        >
                            <i class="pi pi-video fs-3 text-primary"></i>
                        </div>

                        <div class="flex-grow-1 overflow-hidden">
                            <strong class="d-block text-truncate">{{ media.name }}</strong>
                            <small class="d-block text-muted text-truncate">
                                {{ media.original_name }} · {{ formatSize(media.size_bytes) }} · {{ mediaType(media.type) }}
                            </small>
                        </div>

                        <div class="d-flex flex-column align-items-start gap-2">
                            <Tag
                                :value="approvalStatus(media.approval_status).label"
                                :severity="approvalStatus(media.approval_status).severity"
                            />
                            <div class="d-flex align-items-center gap-2">
                                <Tag
                                    :value="distributionSummary(media.id).label"
                                    :severity="distributionSummary(media.id).severity"
                                    :icon="distributionSummary(media.id).icon"
                                />
                                <i
                                    v-if="distributionSummary(media.id).status === 'pending'"
                                    class="pi pi-question-circle text-primary"
                                    v-tooltip.top="'A mídia ainda precisa ser enviada aos pontos vinculados. O status será atualizado quando o player iniciar e concluir o download.'"
                                ></i>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="p-4 text-center text-muted border rounded-3">
                    Nenhuma mídia vinculada.
                </div>
            </section>
        </div>

        <template #footer>
            <Button
                label="Fechar"
                severity="secondary"
                outlined
                @click="visible = false"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.campaign-media-preview {
    width: 84px;
    height: 58px;
    flex: 0 0 84px;
    object-fit: cover;
    background: var(--p-surface-100);
}
</style>
