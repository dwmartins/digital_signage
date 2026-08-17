<script setup>
import CampaignStepContent from "@/components/customer/onboarding/CampaignStepContent.vue";
import DisplayPointStepContent from "@/components/customer/onboarding/DisplayPointStepContent.vue";
import MediaStepContent from "@/components/customer/onboarding/MediaStepContent.vue";
import PlanStepContent from "@/components/customer/onboarding/PlanStepContent.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import Spinner from "@/components/shared/Spinner.vue";
import { showAlert } from "@/helpers/alert";
import customerCampaignService from "@/services/customer-campaign.service";
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const completed = ref(false);
const activeStep = ref("1");
const plans = ref([]);
const categories = ref([]);
const displayPoints = ref([]);
const libraryMedia = ref([]);

const form = reactive({
    plan_id: null,
    name: "",
    description: "",
    category_ids: [],
    display_point_ids: [],
    media_asset_ids: [],
    playback_mode: "sequential",
    files: [],
    media_order: [],
});

const selectedPlan = computed(() => plans.value.find((plan) => plan.id === form.plan_id) ?? null);
const selectedOrientations = computed(() => displayPoints.value
    .filter((point) => form.display_point_ids.includes(point.id))
    .map((point) => point.orientation));

const fetchOptions = async () => {
    try {
        loading.value = true;
        const response = await customerCampaignService.options();
        plans.value = response.plans ?? [];
        categories.value = response.categories ?? [];
        displayPoints.value = response.display_points ?? [];
        libraryMedia.value = response.media_assets ?? [];
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const selectPlan = (planId) => {
    if (form.plan_id !== planId) {
        form.files = [];
        form.media_order = [];
        form.playback_mode = "sequential";
        form.display_point_ids = [];
        form.media_asset_ids = [];
    }

    form.plan_id = planId;
};

const nextFromPlan = () => {
    if (!selectedPlan.value) {
        return showAlert("warning", "Selecione um plano para continuar.");
    }

    activeStep.value = "2";
};

const nextFromCampaign = () => {
    if (!form.name.trim()) {
        return showAlert("warning", "Informe o nome da campanha para continuar.");
    }

    activeStep.value = "3";
};

const toggleDisplayPoint = (pointId) => {
    const index = form.display_point_ids.indexOf(pointId);

    if (index >= 0) {
        form.display_point_ids.splice(index, 1);
        return;
    }

    if (form.display_point_ids.length >= selectedPlan.value.screen_limit) {
        return showAlert("warning", `Seu plano permite até ${selectedPlan.value.screen_limit} ponto(s) de exibição.`);
    }

    form.display_point_ids.push(pointId);
};

const nextFromDisplayPoints = () => {
    if (!form.display_point_ids.length) {
        return showAlert("warning", "Selecione ao menos um ponto de exibição para continuar.");
    }

    activeStep.value = "4";
};

const videoDuration = (file) => new Promise((resolve) => {
    const video = document.createElement("video");
    const url = URL.createObjectURL(file);

    video.preload = "metadata";
    video.onloadedmetadata = () => {
        URL.revokeObjectURL(url);
        resolve(video.duration);
    };
    video.onerror = () => {
        URL.revokeObjectURL(url);
        resolve(null);
    };
    video.src = url;
});

const addFiles = async (newFiles) => {
    if (!selectedPlan.value) return;

    const expectedPrefix = selectedPlan.value.media_type === "video" ? "video/" : "image/";
    const available = selectedPlan.value.media_limit
        - form.files.length
        - form.media_asset_ids.length;
    const candidates = newFiles.slice(0, available);

    if (newFiles.length > available) {
        showAlert("warning", `Seu plano permite no máximo ${selectedPlan.value.media_limit} mídia(s).`);
    }

    for (const file of candidates) {
        if (!file.type.startsWith(expectedPrefix)) {
            showAlert("warning", `O plano selecionado aceita apenas ${selectedPlan.value.media_type === "video" ? "vídeos" : "imagens"}.`);
            continue;
        }

        if (file.size > 100 * 1024 * 1024) {
            showAlert("warning", `O arquivo ${file.name} ultrapassa o limite de 100 MB.`);
            continue;
        }

        if (selectedPlan.value.media_type === "video") {
            const duration = await videoDuration(file);

            if (!duration || duration > 15) {
                showAlert("warning", `O vídeo ${file.name} deve possuir no máximo 15 segundos.`);
                continue;
            }
        }

        form.files.push(file);
    }

    syncOrder();
};

const removeFile = (index) => {
    form.files.splice(index, 1);
    form.media_order = form.media_order.filter((key) => !key.startsWith("file:"));
    syncOrder();
};

const toggleLibraryMedia = (mediaId) => {
    const index = form.media_asset_ids.indexOf(mediaId);

    if (index >= 0) {
        form.media_asset_ids.splice(index, 1);
        form.media_order = form.media_order.filter((key) => key !== `library:${mediaId}`);
        return syncOrder();
    }

    if (form.files.length + form.media_asset_ids.length >= selectedPlan.value.media_limit) {
        return showAlert("warning", `Seu plano permite no máximo ${selectedPlan.value.media_limit} mídia(s).`);
    }

    form.media_asset_ids.push(mediaId);
    syncOrder();
};

const reorderMedia = (from, to) => {
    const [key] = form.media_order.splice(from, 1);
    form.media_order.splice(to, 0, key);
};

const syncOrder = () => {
    const validKeys = [
        ...form.media_asset_ids.map((id) => `library:${id}`),
        ...form.files.map((_, index) => `file:${index}`),
    ];

    form.media_order = [
        ...form.media_order.filter((key) => validKeys.includes(key)),
        ...validKeys.filter((key) => !form.media_order.includes(key)),
    ];
};

const submit = async () => {
    if (!form.files.length && !form.media_asset_ids.length) {
        return showAlert("warning", "Adicione ao menos uma mídia para finalizar.");
    }

    try {
        saving.value = true;
        syncOrder();
        const response = await customerCampaignService.create(form);
        completed.value = true;
        showAlert("success", response.message);
    } catch (error) {
        showAlert("error", error.response?.data ?? error.message);
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchOptions();
});
</script>

<template>
    <section class="container onboarding-view py-3 py-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <Breadcrumb
                    :items="[
                        { icon: 'pi pi-home', to: '/painel/inicio' },
                        { label: 'Começar campanha' },
                    ]"
                />
                <h1 class="h3 fw-bold mt-3 mb-1">Crie sua campanha</h1>
                <p class="text-muted mb-0">Vamos preparar sua divulgação em poucos passos.</p>
            </div>

            <Button
                label="Voltar"
                icon="pi pi-arrow-left"
                severity="secondary"
                outlined
                size="small"
                class="align-self-end"
                @click="router.push({ name: 'customer.dashboard' })"
            />
        </div>

        <Card v-if="loading" class="border-0 shadow-sm">
            <template #content>
                <div class="d-flex justify-content-center align-items-center loading-state">
                    <Spinner />
                </div>
            </template>
        </Card>

        <div v-else-if="completed" class="completion-card text-center rounded-4 shadow-sm p-4 p-md-5">
            <span class="completion-icon d-inline-grid align-items-center justify-content-center rounded-circle mb-4">
                <i class="pi pi-check"></i>
            </span>
            <h2 class="h3 fw-bold mb-3">Tudo pronto por aqui!</h2>
            <p class="text-muted mx-auto mb-4">
                Sua campanha e contratação foram enviadas. Agora nossa equipe fará a análise do conteúdo e dará continuidade à ativação.
            </p>
            <Button
                label="Voltar para a dashboard"
                icon="pi pi-home"
                @click="router.push({ name: 'customer.dashboard' })"
            />
        </div>

        <Card v-else class="onboarding-card border-0 shadow-sm">
            <template #content>
                <Stepper v-model:value="activeStep" linear>
                    <StepList>
                        <Step value="1">Escolha do plano</Step>
                        <Step value="2">Dados da campanha</Step>
                        <Step value="3">Pontos de exibição</Step>
                        <Step value="4">Mídias e ordem</Step>
                    </StepList>

                    <StepPanels>
                        <StepPanel value="1">
                            <PlanStepContent
                                :plans="plans"
                                :selectedId="form.plan_id"
                                @select="selectPlan"
                            />
                            <div class="step-actions d-flex justify-content-end mt-4 pt-4">
                                <Button label="Continuar" icon="pi pi-arrow-right" iconPos="right" @click="nextFromPlan" />
                            </div>
                        </StepPanel>

                        <StepPanel value="2">
                            <CampaignStepContent :form="form" :categories="categories" />
                            <div class="step-actions d-flex justify-content-between mt-4 pt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" severity="secondary" outlined @click="activeStep = '1'" />
                                <Button label="Continuar" icon="pi pi-arrow-right" iconPos="right" @click="nextFromCampaign" />
                            </div>
                        </StepPanel>

                        <StepPanel value="3">
                            <DisplayPointStepContent
                                v-if="selectedPlan"
                                :points="displayPoints"
                                :selectedIds="form.display_point_ids"
                                :limit="selectedPlan.screen_limit"
                                @toggle="toggleDisplayPoint"
                            />
                            <div class="step-actions d-flex justify-content-between mt-4 pt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" severity="secondary" outlined @click="activeStep = '2'" />
                                <Button label="Continuar" icon="pi pi-arrow-right" iconPos="right" @click="nextFromDisplayPoints" />
                            </div>
                        </StepPanel>

                        <StepPanel value="4">
                            <MediaStepContent
                                v-if="selectedPlan"
                                :form="form"
                                :plan="selectedPlan"
                                :orientations="selectedOrientations"
                                :libraryMedia="libraryMedia"
                                @add-files="addFiles"
                                @remove-file="removeFile"
                                @toggle-library="toggleLibraryMedia"
                                @reorder="reorderMedia"
                            />
                            <div class="step-actions d-flex justify-content-between mt-4 pt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" severity="secondary" outlined @click="activeStep = '3'" />
                                <Button label="Enviar campanha" icon="pi pi-check" :loading="saving" @click="submit" />
                            </div>
                        </StepPanel>
                    </StepPanels>
                </Stepper>

            </template>
        </Card>
    </section>
</template>

<style scoped>
.onboarding-view {
    min-height: calc(100vh - 5rem);
}

.onboarding-card {
    border: 1px solid var(--p-content-border-color) !important;
}

.loading-state {
    min-height: 55vh;
}

.step-actions {
    border-top: 1px solid var(--p-content-border-color);
}

.completion-card {
    border: 1px solid var(--p-content-border-color);
    background:
        radial-gradient(circle at top, color-mix(in srgb, var(--p-green-500) 14%, transparent), transparent 48%),
        var(--p-content-background);
}

.completion-card p {
    max-width: 620px;
    line-height: 1.7;
}

.completion-icon {
    width: 5rem;
    height: 5rem;
    color: #ffffff;
    background: var(--p-green-500);
    box-shadow: 0 1rem 2rem color-mix(in srgb, var(--p-green-500) 25%, transparent);
}

.completion-icon i {
    font-size: 2rem;
}

@media (max-width: 767.98px) {
    .onboarding-card {
        max-width: 100%;
        overflow: hidden;
    }

    .onboarding-card :deep(.p-step-title) {
        display: none;
    }

    .onboarding-card :deep(.p-step) {
        min-width: 0;
    }

    .onboarding-card :deep(.p-step-header) {
        flex-shrink: 0;
        justify-content: center;
    }

    .onboarding-card :deep(.p-card-body),
    .onboarding-card :deep(.p-card-content) {
        padding-inline: 0.75rem;
    }

    .onboarding-card :deep(.p-steppanel-content) {
        min-width: 0;
        padding-inline: 0;
    }

    .step-actions {
        gap: 0.5rem;
    }
}
</style>
