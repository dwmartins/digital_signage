<script setup>
import { showAlert } from "@/helpers/alert";
import campaignService from "@/services/campaign.service";
import localityService from "@/services/locality.service";
import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import { useRouter } from 'vue-router';
import { API_URL } from '@/helpers/constants';

const props = defineProps({ modelValue: Boolean, campaign: Object, categories: Array, subscriptions: Array, displayPoints: Array });

const emit = defineEmits(["update:modelValue", "saved", "cancelled", "media-detached"]);

const saving = ref(false);
const detaching = ref(false);
const mediaToDetach = ref(null);
const displayPointDialog = ref(false);
const draftDisplayPointIds = ref([]);
const pointResults = ref([]);
const pointPagination = ref({ total: 0, current_page: 1 });
const pointRows = ref(5);
const loadingPoints = ref(false);
const filePreviewUrls = ref([]);
const contentSource = ref("upload");
const libraryMedia = ref([]);
const loadingLibrary = ref(false);
const draggingMediaKey = ref(null);
const dragOverMediaKey = ref(null);
const errors = reactive({});
const states = ref([]);
const cities = ref([]);
const neighborhoods = ref([]);
const pointFilters = reactive({ search: "", state_id: null, city_id: null, neighborhood_id: null });

const form = reactive({ id: null, subscription_id: null, name: "", description: "", playback_mode: "sequential", status: "active", category_ids: [], display_point_ids: [], files: [], media_asset_ids: [], media_order: [] });
const campaignStatusOptions = [
    { label: "Ativa", value: "active" },
    { label: "Inativa", value: "inactive" },
];
const playbackModeOptions = [
    { label: "Em sequência", value: "sequential" },
    { label: "Ordem aleatória", value: "random" },
];

const visible = computed({ get: () => props.modelValue, set: (value) => emit("update:modelValue", value) });
const isUpdate = computed(() => !!props.campaign?.id);
const availableCities = computed(() => cities.value.filter((city) => city.state_id === pointFilters.state_id));
const availableNeighborhoods = computed(() => neighborhoods.value.filter((neighborhood) => neighborhood.city_id === pointFilters.city_id));

const subscriptionOptions = computed(() => isUpdate.value && props.campaign?.subscription
    ? [props.campaign.subscription, ...props.subscriptions.filter((item) => item.id !== props.campaign.subscription.id)]
    : props.subscriptions);

const selectedSubscription = computed(() => subscriptionOptions.value.find((item) => item.id === form.subscription_id));
const accept = computed(() => selectedSubscription.value?.media_type === "video" ? ".mp4,.webm,.mov" : ".jpg,.jpeg,.png,.webp");

const router = useRouter();

const campaignMedia = computed(() => props.campaign?.media_assets ?? props.campaign?.mediaAssets ?? []);
const customerId = computed(() => props.campaign?.user_id ?? selectedSubscription.value?.user_id ?? null);
const mediaLimit = computed(() => Number(selectedSubscription.value?.media_limit ?? 1));
const supportsMultipleMedia = computed(() => mediaLimit.value > 1);
const mediaCapacity = computed(() => Math.max(mediaLimit.value - campaignMedia.value.length, 0));
const availableMediaSlots = computed(() => Math.max(
    mediaCapacity.value - form.files.length - form.media_asset_ids.length,
    0,
));
const availableLibraryMedia = computed(() => libraryMedia.value.filter(
    (media) => !campaignMedia.value.some((current) => current.id === media.id),
));
const selectedDisplayPoints = computed(() => props.displayPoints.filter((point) => form.display_point_ids.includes(point.id)));
const displayPointLimit = computed(() => Number(selectedSubscription.value?.screen_limit ?? 0));
const displayPointLimitReached = computed(() => displayPointLimit.value > 0
    && form.display_point_ids.length >= displayPointLimit.value);

const draftDisplayPointLimitReached = computed(() => displayPointLimit.value > 0
    && draftDisplayPointIds.value.length >= displayPointLimit.value);

const displayPointLimitMessage = computed(() => `O plano permite no máximo ${displayPointLimit.value} ponto(s) de exibição.`);

const mediaKey = (media) => media.isNew ? `file:${media.fileIndex}` : `media:${media.id}`;
const selectedMedia = computed(() => [
    ...campaignMedia.value,
    ...libraryMedia.value.filter((media) => form.media_asset_ids.includes(media.id)
        && !campaignMedia.value.some((current) => current.id === media.id)),
    ...form.files.map((file, index) => ({
        id: `new-${index}`,
        fileIndex: index,
        name: file.name,
        original_name: file.name,
        size_bytes: file.size,
        type: selectedSubscription.value?.media_type,
        content_url: filePreviewUrls.value[index] ?? null,
        isNew: true,
    })),
]);
const displayedMedia = computed(() => {
    const mediaMap = new Map(selectedMedia.value.map((media) => [mediaKey(media), media]));
    const ordered = form.media_order
        .map((key) => mediaMap.get(key))
        .filter(Boolean);
    const orderedKeys = new Set(ordered.map(mediaKey));

    return [...ordered, ...selectedMedia.value.filter((media) => !orderedKeys.has(mediaKey(media)))];
});

const formatSize = (bytes) =>
    bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.ceil(bytes / 1024)} KB`;

const editMedia = (media) => {
    visible.value = false;
    router.push({ name: 'platform.media', query: { media_id: media.id } });
};

const viewAllMedia = () => {
    if (!customerId.value) return;
    visible.value = false;
    router.push({ name: 'platform.media', query: { user_id: customerId.value } });
};

const detachMedia = async () => {
    if (!mediaToDetach.value || !props.campaign?.id) return;
    try {
        detaching.value = true;
        const response = await campaignService.detachMedia(props.campaign.id, mediaToDetach.value.id);
        showAlert("success", response.message);
        mediaToDetach.value = null;
        emit("media-detached", response.campaign);
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        detaching.value = false;
    }
};

const clearFilePreview = () => {
    filePreviewUrls.value.forEach((url) => URL.revokeObjectURL(url));
    filePreviewUrls.value = [];
};

const fetchLibraryMedia = async () => {
    if (!form.subscription_id) {
        libraryMedia.value = [];
        return;
    }
    try {
        loadingLibrary.value = true;
        const response = await campaignService.mediaOptions(form.subscription_id);
        libraryMedia.value = response.data ?? [];
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loadingLibrary.value = false;
    }
};

const changeContentSource = (source) => {
    contentSource.value = source;
    form.files = [];
    form.media_asset_ids = [];
    clearFilePreview();
};

const toggleDisplayPoint = (id) => {
    if (!draftDisplayPointIds.value.includes(id)
        && displayPointLimit.value > 0
        && draftDisplayPointIds.value.length >= displayPointLimit.value) {
        return showAlert("warning", displayPointLimitMessage.value);
    }
    draftDisplayPointIds.value = draftDisplayPointIds.value.includes(id)
        ? draftDisplayPointIds.value.filter((item) => item !== id)
        : [...draftDisplayPointIds.value, id];
};

const openDisplayPointDialog = () => {
    draftDisplayPointIds.value = [...form.display_point_ids];
    displayPointDialog.value = true;
    fetchDisplayPoints(1);
};

const confirmDisplayPoints = () => {
    form.display_point_ids = [...draftDisplayPointIds.value];
    displayPointDialog.value = false;
};

const clearPointFilters = () => {
    Object.assign(pointFilters, { search: "", state_id: null, city_id: null, neighborhood_id: null });
    pointResults.value = [];
    pointPagination.value = { total: 0, current_page: 1 };
};

const fetchDisplayPoints = async (page = 1) => {
    try {
        loadingPoints.value = true;
        const filters = {
            global: pointFilters.search || undefined,
            state_id: pointFilters.state_id || undefined,
            city_id: pointFilters.city_id || undefined,
            neighborhood_id: pointFilters.neighborhood_id || undefined,
        };
        const response = await campaignService.displayPointOptions(page, pointRows.value, filters);
        pointResults.value = response.data ?? [];
        pointPagination.value = response.pagination ?? { total: 0, current_page: 1 };
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loadingPoints.value = false;
    }
};

const fetchLocalities = async () => {
    try {
        const response = await localityService.options();
        states.value = response.states ?? [];
        cities.value = response.cities ?? [];
        neighborhoods.value = response.neighborhoods ?? [];
    } catch (error) {
        showAlert("error", error.response?.data);
    }
};

const removeDisplayPoint = (id) => {
    form.display_point_ids = form.display_point_ids.filter((item) => item !== id);
};

const moveMedia = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= form.media_order.length) return;
    const order = [...form.media_order];
    [order[index], order[target]] = [order[target], order[index]];
    form.media_order = order;
};

const startMediaDrag = (event, media) => {
    draggingMediaKey.value = mediaKey(media);
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', draggingMediaKey.value);
};

const dropMedia = (targetMedia) => {
    const sourceKey = draggingMediaKey.value;
    const targetKey = mediaKey(targetMedia);
    if (!sourceKey || sourceKey === targetKey) return clearMediaDrag();

    const order = [...form.media_order];
    const sourceIndex = order.indexOf(sourceKey);
    const targetIndex = order.indexOf(targetKey);
    if (sourceIndex === -1 || targetIndex === -1) return clearMediaDrag();

    order.splice(sourceIndex, 1);
    order.splice(targetIndex, 0, sourceKey);
    form.media_order = order;
    clearMediaDrag();
};

const clearMediaDrag = () => {
    draggingMediaKey.value = null;
    dragOverMediaKey.value = null;
};

const money = (value) => new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(value ?? 0);
const customerName = (item) => `${item?.customer?.name ?? ""} ${item?.customer?.last_name ?? ""}`.trim();

const validateVideo = (file) => new Promise((resolve) => {
    const video = document.createElement("video");
    const url = URL.createObjectURL(file);
    video.preload = "metadata";
    video.onloadedmetadata = () => { URL.revokeObjectURL(url); resolve(video.duration <= 15); };
    video.onerror = () => { URL.revokeObjectURL(url); resolve(false); };
    video.src = url;
});

const selectFile = async (event) => {
    const selectedFiles = event.files ?? [];
    const files = selectedFiles.filter((file) => !form.files.some((current) =>
        current.name === file.name
        && current.size === file.size
        && current.lastModified === file.lastModified,
    ));
    if (!files.length) return showAlert("warning", "Os arquivos selecionados já foram adicionados.");
    if (files.length > availableMediaSlots.value) {
        return showAlert("warning", `O plano permite adicionar mais ${availableMediaSlots.value} mídia(s) nesta campanha.`);
    }
    const expected = selectedSubscription.value?.media_type;
    if (files.some((file) => !file.type.startsWith(`${expected}/`))) return showAlert("warning", `Esta assinatura aceita apenas ${expected === "video" ? "vídeos" : "imagens"}.`);
    if (expected === "video") {
        const validVideos = await Promise.all(files.map(validateVideo));
        if (validVideos.some((valid) => !valid)) return showAlert("warning", "Todos os vídeos devem possuir no máximo 15 segundos.");
    }
    form.media_asset_ids = [];
    if (expected === "image") {
        filePreviewUrls.value.push(...files.map((file) => URL.createObjectURL(file)));
    }
    form.files.push(...files);
};

const submit = async () => {
    errors.subscription_id = form.subscription_id ? null : "Selecione uma assinatura disponível.";
    errors.name = form.name.trim() ? null : "Informe o nome.";
    errors.file = !isUpdate.value && !form.files.length && !form.media_asset_ids.length ? "Envie ao menos um arquivo ou selecione uma mídia da Biblioteca." : null;
    if (Object.values(errors).some(Boolean)) return;
    form.media_order = displayedMedia.value.map(mediaKey);
    try {
        saving.value = true;
        const response = isUpdate.value ? await campaignService.update(form) : await campaignService.create(form);
        showAlert("success", response.message);
        emit("saved"); visible.value = false;
    } catch (error) { showAlert("error", error.response?.data); } finally { saving.value = false; }
};

watch(() => form.subscription_id, () => {
    form.files = [];
    form.media_asset_ids = [];
    clearFilePreview();
    if (!supportsMultipleMedia.value) form.playback_mode = "sequential";
    fetchLibraryMedia();
});

watch(() => form.media_asset_ids, (value) => {
    if (!value.length) return;
    form.files = [];
    clearFilePreview();
}, { deep: true });

watch(() => selectedMedia.value.map(mediaKey), (keys) => {
    form.media_order = [
        ...form.media_order.filter((key) => keys.includes(key)),
        ...keys.filter((key) => !form.media_order.includes(key)),
    ];
}, { immediate: true });

watch(() => displayPointDialog.value, (opened) => {
    if (!opened) {
        clearPointFilters();
        pointResults.value = [];
        pointPagination.value = { total: 0, current_page: 1 };
    }
});

watch(() => pointFilters.state_id, () => {
    pointFilters.city_id = null;
    pointFilters.neighborhood_id = null;
});

watch(() => pointFilters.city_id, () => {
    pointFilters.neighborhood_id = null;
});

watch(() => props.modelValue, (opened) => {
    if (!opened) return;
    fetchLocalities();
    const campaign = props.campaign;
    Object.assign(form, {
        id: campaign?.id ?? null, subscription_id: campaign?.subscription?.id ?? null,
        name: campaign?.name ?? "", description: campaign?.description ?? "",
        playback_mode: campaign?.playback_mode ?? "sequential", status: campaign?.status ?? "active",
        category_ids: campaign?.categories?.map((item) => item.id) ?? [],
        display_point_ids: campaign?.display_points?.map((item) => item.id) ?? [],
        files: [], media_asset_ids: [], media_order: campaignMedia.value.map(mediaKey)
    });
    contentSource.value = "upload";
    Object.keys(errors).forEach((key) => delete errors[key]);
}, { immediate: true });

onBeforeUnmount(clearFilePreview);
</script>

<template>
    <Card>
        <template #content>
        <form id="campaignForm" class="row g-4" @submit.prevent="submit">
            <div class="col-12">
                <Divider align="left" class="mb-0"><b>Contratação</b></Divider>
            </div>

            <div v-if="!selectedSubscription" class="col-12">
                <div class="field">
                    <label>
                        <span class="text-danger me-1">*</span>Assinatura sem campanha
                    </label>
                    <Select v-model="form.subscription_id" :options="subscriptionOptions" optionLabel="id"
                        optionValue="id" :disabled="isUpdate" filter fluid :invalid="!!errors.subscription_id"
                        placeholder="Selecione a assinatura criada anteriormente">
                        <template #option="{ option }">
                            <div class="d-flex flex-column"><strong>#{{ option.id }} - {{ option.plan?.name
                                    }}</strong><small>{{ customerName(option) }} · {{ option.media_type === 'video' ?
                                    'Vídeo' : 'Imagem' }} · {{ money(option.price) }}</small></div>
                        </template>
                        <template #value="{ value }"><span v-if="value">#{{ value }} - {{
                                selectedSubscription?.plan?.name }} · {{ customerName(selectedSubscription)
                                }}</span>
                        </template>
                    </Select>
                    <small v-if="errors.subscription_id" class="text-danger">{{ errors.subscription_id }}</small>
                </div>
            </div>

            <div v-if="selectedSubscription" class="col-12">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="plan-icon">
                            <i class="pi pi-verified"></i>
                        </div>
                        <div class="flex-grow-1">
                            <small class="plan-eyebrow">Assinatura #{{ selectedSubscription.id }}</small>
                            <strong>{{ selectedSubscription.plan?.name }}</strong>
                            <span>{{ customerName(selectedSubscription) }}</span>
                        </div>
                        <Tag value="Contratação selecionada" severity="info" icon="pi pi-check-circle me-1" class="d-none d-sm-block"/>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 col-lg-3">
                            <div class="d-flex align-items-start gap-2 h-100 p-3 border rounded-3 plan-detail-item">
                                <i :class="selectedSubscription.media_type === 'video' ? 'pi pi-video' : 'pi pi-image'"></i>
                                <div>
                                    <small>Tipo de mídia</small>
                                    <strong>{{ selectedSubscription.media_type === 'video' ? 'Vídeo' : 'Imagem' }}</strong>
                                    <span v-if="selectedSubscription.media_type === 'video'">Até 15 segundos</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="d-flex align-items-start gap-2 h-100 p-3 border rounded-3 plan-detail-item">
                                <i class="pi pi-images"></i>
                                <div>
                                    <small>Limite de mídias</small>
                                    <strong>Até {{ selectedSubscription.media_limit }}</strong>
                                    <span>{{ selectedSubscription.media_limit === 1 ? 'mídia' : 'mídias' }} na campanha</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="d-flex align-items-start gap-2 h-100 p-3 border rounded-3 plan-detail-item">
                                <i class="pi pi-desktop"></i>
                                <div>
                                    <small>Limite de telas</small>
                                    <strong>Até {{ selectedSubscription.screen_limit }}</strong>
                                    <span>{{ selectedSubscription.screen_limit === 1 ? 'tela' : 'telas' }} simultâneas</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="d-flex align-items-start gap-2 h-100 p-3 border rounded-3 plan-detail-item">
                                <i class="pi pi-calendar"></i>
                                <div>
                                    <small>Ciclo</small>
                                    <strong>{{ selectedSubscription.billing_cycle === 'yearly' ? 'Anual' : 'Mensal' }}</strong>
                                    <span>{{ selectedSubscription.billing_cycle === 'yearly' ? 'Renovação anual' : 'Renovação a cada 30 dias' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="d-flex align-items-start gap-2 h-100 p-3 border rounded-3 plan-detail-item plan-price">
                                <i class="pi pi-wallet"></i>
                                <div>
                                    <small>Valor contratado</small>
                                    <strong>{{ money(selectedSubscription.price) }}</strong>
                                    <span>{{ Number(selectedSubscription.price) === 0 ? 'Assinatura gratuita' : 'Valor negociado' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <Divider align="left"><b>Distribuição</b></Divider>
            </div>
            <div class="col-12">
                <div class="field">
                    <label>Pontos de exibição</label>
                    <div v-if="selectedDisplayPoints.length" class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                        <div><strong>{{ selectedDisplayPoints.length }} ponto(s) selecionado(s)</strong><small class="d-block text-muted">Limite do plano: {{ displayPointLimit }} ponto(s)</small></div>
                        <span v-tooltip.top="displayPointLimitReached ? displayPointLimitMessage : null">
                            <Button label="Selecionar mais locais" icon="pi pi-plus" outlined
                                :disabled="displayPointLimitReached" @click="openDisplayPointDialog" />
                        </span>
                    </div>
                    <small class="text-muted">A mídia somente ficará disponível para exibição quando todos os requisitos estiverem válidos.</small>
                </div>

                <div v-if="selectedDisplayPoints.length" class="row g-2 mt-3">
                    <div v-for="point in selectedDisplayPoints" :key="point.id" class="col-12 col-md-6">
                        <article class="d-flex align-items-start gap-2 h-100 p-3 border rounded-3 selected-point-card">
                            <i class="pi pi-map-marker"></i>
                            <div class="flex-grow-1"><strong>{{ point.establishment?.name }}</strong><span>{{ point.name }} · {{ point.location || 'Local não informado' }}</span><small>{{ point.orientation === 'portrait' ? 'Vertical' : 'Horizontal' }} · {{ point.establishment?.opening_hours || 'Horário não informado' }}</small></div>
                            <Button icon="pi pi-times" severity="danger" text rounded size="small" v-tooltip.top="'Desvincular ponto'" @click="removeDisplayPoint(point.id)" />
                        </article>
                    </div>
                </div>

                <div v-else class="d-flex flex-column align-items-center justify-content-center gap-2 mt-3 p-4 text-center border rounded-3 no-selected-points">
                    <div class="no-selected-points-icon"><i class="pi pi-map-marker"></i></div>
                    <strong>Nenhum ponto de exibição selecionado</strong>
                    <span>Escolha os locais onde esta campanha poderá ser exibida.</span>
                    <Button label="Selecionar pontos de exibição" icon="pi pi-map-marker" @click="openDisplayPointDialog" />
                </div>
            </div>

            <div class="col-12">
                <Divider align="left"><b>Conteúdo e classificação</b></Divider>
            </div>
            <div class="col-md-6">
                <div class="field"><label><span class="text-danger me-1">*</span>Nome da campanha e da mídia</label>
                    <InputText v-model="form.name" maxlength="255" fluid :invalid="!!errors.name" /><small
                        v-if="errors.name" class="text-danger">{{ errors.name }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label class="d-flex align-items-center gap-2">
                        Status da campanha
                        <i
                            class="pi pi-question-circle text-primary cursor-help"
                            v-tooltip.top="'Uma campanha ativa fica habilitada para exibição quando os demais requisitos forem atendidos. Uma campanha inativa não será exibida.'"
                        ></i>
                    </label>
                    <Select
                        v-model="form.status"
                        :options="campaignStatusOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div v-if="supportsMultipleMedia" class="col-md-6">
                <div class="field">
                    <label class="d-flex align-items-center gap-2">
                        Ordem de reprodução
                        <i
                            class="pi pi-question-circle text-primary cursor-help"
                            v-tooltip.top="'Define como as mídias desta campanha serão alternadas nos pontos de exibição.'"
                        ></i>
                    </label>
                    <Select
                        v-model="form.playback_mode"
                        :options="playbackModeOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                    <small class="text-muted">
                        {{ form.playback_mode === 'random'
                            ? 'As mídias serão escolhidas em ordem aleatória.'
                            : 'As mídias serão exibidas seguindo a ordem da campanha.' }}
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field"><label>Categorias</label>
                    <MultiSelect v-model="form.category_ids" :options="categories" optionLabel="name" optionValue="id"
                        placeholder="Opcional" filter display="chip" fluid />
                </div>
            </div>
            <div class="col-12">
                <div class="field"><label>Observação</label><Textarea v-model="form.description" rows="3"
                        maxlength="5000" autoResize fluid /></div>
            </div>
            <div class="col-12">
                <div class="field"><label><span v-if="!isUpdate" class="text-danger me-1">*</span>Arquivo da
                        mídia</label>

                    <div v-if="displayedMedia.length" class="p-3 border rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                            <div>
                                <strong>Mídias da campanha</strong>
                                <small class="d-block text-muted">
                                    {{ displayedMedia.length }} de {{ mediaLimit }} mídia(s) utilizada(s)
                                </small>
                            </div>
                            <Tag :value="selectedSubscription?.media_type === 'video' ? 'Vídeo' : 'Imagem'" :icon="selectedSubscription?.media_type === 'video' ? 'pi pi-video' : 'pi pi-image'" severity="info" />
                        </div>
                        <div v-if="selectedSubscription?.media_type === 'image'" class="row g-3">
                            <div v-for="media in displayedMedia" :key="media.id" class="col-12 col-sm-6 col-lg-4 col-xl-3">
                                <article class="h-100 overflow-hidden border rounded-3 media-image">
                                    <img :src="media.isNew ? media.content_url : `${API_URL}/media-assets/${media.id}/content`" :alt="media.name" />
                                    <div class="d-flex flex-column gap-1 p-2"><strong class="text-truncate">{{ media.original_name ?? media.name }}</strong><small class="text-muted">{{ formatSize(media.size_bytes) }}</small>
                                        <Button v-if="!media.isNew" size="small" label="Gerenciar mídia" icon="pi pi-external-link" text @click="editMedia(media)" />
                                        <Button v-if="!media.isNew && isUpdate" size="small" label="Desvincular" icon="pi pi-link" severity="danger" text @click="mediaToDetach = media" />
                                    </div>
                                </article>
                            </div>
                        </div>
                        <div v-else class="d-flex flex-column gap-2">
                            <article v-for="media in displayedMedia" :key="media.id" class="d-flex align-items-center gap-3 p-3 border rounded-3">
                                <div class="video-icon"><i class="pi pi-video"></i></div>
                                <div class="flex-grow-1 overflow-hidden"><strong class="d-block text-truncate">{{ media.original_name ?? media.name }}</strong><small class="text-muted">Vídeo · {{ formatSize(media.size_bytes) }} · máximo de 15 segundos</small></div>
                                <Button v-if="!media.isNew" icon="pi pi-external-link" label="Gerenciar" size="small" outlined @click="editMedia(media)" />
                                <Button v-if="!media.isNew && isUpdate" icon="pi pi-link" label="Desvincular" size="small" severity="danger" outlined @click="mediaToDetach = media" />
                            </article>
                        </div>
                    </div>

                    <div v-if="selectedSubscription" class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <button type="button" class="d-flex align-items-center gap-3 w-100 h-100 p-3 border rounded-3 text-start source-option" :class="{ active: contentSource === 'upload' }" @click="changeContentSource('upload')">
                                <i class="pi pi-upload"></i><span><strong>Enviar do dispositivo</strong><small>Escolher no computador, tablet ou celular</small></span>
                            </button>
                        </div>
                        <div class="col-12 col-md-6">
                            <button type="button" class="d-flex align-items-center gap-3 w-100 h-100 p-3 border rounded-3 text-start source-option" :class="{ active: contentSource === 'library' }" @click="changeContentSource('library')">
                                <i class="pi pi-images"></i><span><strong>Escolher da Biblioteca</strong><small>Usar mídias já enviadas pelo anunciante</small></span>
                            </button>
                        </div>
                    </div>

                    <div v-if="contentSource === 'upload'" class="d-flex flex-wrap align-items-center gap-2 p-3 border rounded-3 upload-actions">
                        <FileUpload mode="basic" name="files[]" :accept="accept" :disabled="!selectedSubscription || !availableMediaSlots"
                            chooseLabel="Selecionar arquivos" chooseIcon="pi pi-upload" customUpload auto multiple @select="selectFile" />
                        <Button v-if="customerId" label="Ver todas as mídias do cliente" icon="pi pi-images" severity="secondary" outlined @click="viewAllMedia" />
                        <small class="w-100 text-muted">
                            Você pode adicionar mais {{ availableMediaSlots }} mídia(s) conforme o plano.
                        </small>
                    </div>
                    <div v-else class="p-3 border rounded-3 library-selector">
                        <MultiSelect v-model="form.media_asset_ids" :options="availableLibraryMedia" optionLabel="name" optionValue="id"
                            :loading="loadingLibrary" :disabled="!selectedSubscription || !availableMediaSlots"
                            :selectionLimit="mediaCapacity" filter display="chip" fluid
                            placeholder="Selecione as mídias compatíveis">
                            <template #option="{ option }"><div class="d-flex align-items-center gap-3 py-1 w-100"><div class="library-thumb"><img v-if="option.type === 'image'" :src="option.content_url" :alt="option.name" /><i v-else class="pi pi-video"></i></div><div class="d-flex flex-column flex-grow-1 min-width-0"><strong>{{ option.name }}</strong><small class="text-muted">{{ option.original_name }} · {{ formatSize(option.size_bytes) }}</small></div><Tag :value="option.approval_status === 'approved' ? 'Aprovada' : option.approval_status === 'awaiting_subscription' ? 'Aguardando assinatura' : 'Aguardando aprovação'" :severity="option.approval_status === 'approved' ? 'success' : 'warn'" /></div></template>
                        </MultiSelect>
                        <small v-if="!loadingLibrary && !availableLibraryMedia.length" class="text-muted d-block mt-2">Não há novas mídias compatíveis na Biblioteca deste anunciante.</small>
                    </div>

                    <div v-if="displayedMedia.length > 1" class="mt-3 p-3 border rounded-3">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
                            <div>
                                <strong>Ordem das mídias</strong>
                                <small class="d-block text-muted">
                                    Defina qual conteúdo aparecerá primeiro, segundo e assim por diante.
                                </small>
                            </div>
                            <Tag
                                :value="form.playback_mode === 'random' ? 'Aleatória' : 'Em sequência'"
                                :icon="form.playback_mode === 'random' ? 'pi pi-shuffle' : 'pi pi-list'"
                                severity="info"
                            />
                        </div>

                        <div v-if="form.playback_mode === 'random'" class="p-3 rounded-3 bg-body-tertiary text-muted">
                            <i class="pi pi-info-circle me-2 text-primary"></i>
                            No modo aleatório, o sistema escolherá a próxima mídia sem seguir uma posição fixa.
                        </div>

                        <div v-else class="d-flex flex-column gap-2">
                            <article
                                v-for="(media, index) in displayedMedia"
                                :key="mediaKey(media)"
                                class="d-flex align-items-center gap-3 p-2 border rounded-3 media-order-item"
                                :class="{
                                    dragging: draggingMediaKey === mediaKey(media),
                                    'drag-over': dragOverMediaKey === mediaKey(media),
                                }"
                                @dragover.prevent="dragOverMediaKey = mediaKey(media)"
                                @dragleave="dragOverMediaKey = null"
                                @drop.prevent="dropMedia(media)"
                            >
                                <Tag :value="String(index + 1)" rounded />
                                <img
                                    v-if="media.type === 'image'"
                                    :src="media.isNew ? media.content_url : `${API_URL}/media-assets/${media.id}/content`"
                                    :alt="media.name"
                                    class="media-order-thumb rounded-2"
                                />
                                <div v-else class="media-order-thumb d-flex align-items-center justify-content-center rounded-2">
                                    <i class="pi pi-video text-primary"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <strong class="d-block text-truncate">
                                        {{ media.original_name ?? media.name }}
                                    </strong>
                                    <small class="text-muted">
                                        {{ index === 0 ? 'Primeira mídia' : `${index + 1}ª mídia` }}
                                    </small>
                                </div>
                                <div class="d-flex gap-1">
                                    <Button
                                        type="button"
                                        icon="pi pi-arrow-up"
                                        severity="secondary"
                                        text
                                        rounded
                                        size="small"
                                        :disabled="index === 0"
                                        v-tooltip.top="'Mover para cima'"
                                        @click="moveMedia(index, -1)"
                                    />
                                    <Button
                                        type="button"
                                        icon="pi pi-arrow-down"
                                        severity="secondary"
                                        text
                                        rounded
                                        size="small"
                                        :disabled="index === displayedMedia.length - 1"
                                        v-tooltip.top="'Mover para baixo'"
                                        @click="moveMedia(index, 1)"
                                    />
                                </div>
                                <i
                                    class="pi pi-bars media-drag-handle"
                                    draggable="true"
                                    v-tooltip.left="'Arraste para alterar a posição'"
                                    @dragstart="startMediaDrag($event, media)"
                                    @dragend="clearMediaDrag"
                                ></i>
                            </article>
                        </div>
                    </div>
                    <small v-if="errors.file" class="text-danger">{{ errors.file }}</small>
                    <small v-else-if="isUpdate" class="text-muted d-block">Deixe vazio para manter as mídias atuais.</small>
                </div>
            </div>
        </form>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <Button label="Cancelar" severity="danger" text :disabled="saving" @click="emit('cancelled')" />
            <Button label="Salvar campanha" icon="pi pi-check" type="submit" form="campaignForm" :loading="saving" />
        </div>
        </template>
    </Card>

    <Dialog v-model:visible="displayPointDialog" modal header="Selecionar pontos de exibição"
        :style="{ width: '62rem' }" :breakpoints="{ '992px': '96vw' }" :draggable="false">
        <div class="p-3 border rounded-3 mb-4 point-filter-panel">
            <div class="row g-3">
                <div class="col-12"><div class="field"><label>Buscar</label><InputText v-model="pointFilters.search" placeholder="Nome do ponto, estabelecimento, local, CEP ou endereço" fluid /></div></div>
                <div class="col-md-4">
                    <div class="field">
                        <label>Estado</label>
                        <Select
                            v-model="pointFilters.state_id"
                            :options="states"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Todos"
                            showClear
                            filter
                            fluid
                        >
                            <template #option="{ option }">{{ option.name }} ({{ option.code }})</template>
                        </Select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field">
                        <label>Cidade</label>
                        <Select
                            v-model="pointFilters.city_id"
                            :options="availableCities"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Todas"
                            :disabled="!pointFilters.state_id"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field">
                        <label>Bairro</label>
                        <Select
                            v-model="pointFilters.neighborhood_id"
                            :options="availableNeighborhoods"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Todos"
                            :disabled="!pointFilters.city_id"
                            showClear
                            filter
                            fluid
                        />
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center gap-2 mt-3 flex-wrap"><small class="text-muted">{{ pointPagination.total }} ponto(s) encontrado(s)</small><div class="d-flex gap-2"><Button label="Limpar" icon="pi pi-filter-slash" severity="secondary" outlined size="small" @click="clearPointFilters" /><Button label="Buscar" icon="pi pi-search" size="small" :loading="loadingPoints" @click="fetchDisplayPoints(1)" /></div></div>
        </div>

        <DataTable v-if="pointResults.length || loadingPoints" :value="pointResults" paginator lazy
            :loading="loadingPoints" :rows="pointRows" :totalRecords="pointPagination.total"
            :first="(pointPagination.current_page - 1) * pointRows" :rowsPerPageOptions="[5, 10, 20]"
            scrollable stripedRows class="point-table" @page="(event) => { pointRows = event.rows; fetchDisplayPoints(event.page + 1); }">
            <Column header="Ponto de exibição" style="min-width: 190px"><template #body="{ data }"><div class="d-flex flex-column"><strong>#{{ data.id }} - {{ data.name }}</strong><small class="text-muted">{{ data.location || 'Local não informado' }}</small></div></template></Column>
            <Column header="Orientação" style="width: 125px"><template #body="{ data }"><Tag :value="data.orientation === 'portrait' ? 'Vertical' : 'Horizontal'" :icon="data.orientation === 'portrait' ? 'pi pi-arrows-v' : 'pi pi-arrows-h'" severity="info" /></template></Column>
            <Column header="Estabelecimento" style="min-width: 190px"><template #body="{ data }"><div class="d-flex flex-column"><strong>{{ data.establishment?.name }}</strong><small class="text-muted">{{ data.establishment?.city?.name }}/{{ data.establishment?.city?.state?.code }}</small></div></template></Column>
            <Column header="Endereço" style="min-width: 230px"><template #body="{ data }"><div class="d-flex flex-column"><span>{{ data.establishment?.address }}, {{ data.establishment?.number || 'S/N' }}</span><small class="text-muted">{{ data.establishment?.neighborhood?.name || 'Bairro não informado' }} · CEP {{ data.establishment?.zip_code || 'não informado' }}</small></div></template></Column>
            <Column header="Horário" style="min-width: 180px"><template #body="{ data }"><span><i class="pi pi-clock me-2 text-primary"></i>{{ data.establishment?.opening_hours || 'Não informado' }}</span></template></Column>
            <Column style="width: 120px"><template #header><span class="w-100 text-center">Selecionar</span></template><template #body="{ data }"><div class="d-flex justify-content-center"><span v-tooltip.top="draftDisplayPointLimitReached && !draftDisplayPointIds.includes(data.id) ? displayPointLimitMessage : null"><Button :icon="draftDisplayPointIds.includes(data.id) ? 'pi pi-check' : 'pi pi-plus'" :severity="draftDisplayPointIds.includes(data.id) ? 'success' : 'secondary'" :outlined="!draftDisplayPointIds.includes(data.id)" :disabled="draftDisplayPointLimitReached && !draftDisplayPointIds.includes(data.id)" rounded @click="toggleDisplayPoint(data.id)" /></span></div></template></Column>
        </DataTable>
        <div v-else class="d-flex flex-column align-items-center gap-2 p-5 text-muted border rounded-3 point-empty"><i class="pi pi-map-marker"></i><strong>Nenhum ponto encontrado</strong><span>Preencha os filtros e clique em Buscar.</span></div>

        <template #footer><Button label="Cancelar" severity="secondary" text @click="displayPointDialog = false" /><Button :label="`Confirmar (${draftDisplayPointIds.length})`" icon="pi pi-check" @click="confirmDisplayPoints" /></template>
    </Dialog>

    <Dialog :visible="!!mediaToDetach" modal header="Desvincular mídia" :style="{ width: '30rem' }"
        :breakpoints="{ '768px': '94vw' }" :draggable="false" @update:visible="(value) => { if (!value) mediaToDetach = null }">
        <div class="d-flex align-items-start gap-3">
            <div class="detach-icon"><i class="pi pi-link"></i></div>
            <div><strong>Remover esta mídia da campanha?</strong><p class="text-muted mt-2 mb-0">A mídia continuará disponível na Biblioteca. A campanha permanecerá com o status atual, mas não poderá exibir conteúdo até que outro arquivo seja vinculado.</p></div>
        </div>
        <template #footer>
            <Button label="Cancelar" text :disabled="detaching" @click="mediaToDetach = null" />
            <Button label="Desvincular" icon="pi pi-link" severity="danger" :loading="detaching" @click="detachMedia" />
        </template>
    </Dialog>
</template>

<style scoped>
.detach-icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 10px;
    color: var(--p-red-600);
    background: var(--p-red-50);
}

.min-width-0 {
    min-width: 0;
}

.point-filter-panel {
    background: var(--p-surface-50);
}

.point-empty {
    border-style: dashed !important;
}

.point-empty i {
    font-size: 1.8rem;
}

.selected-point-card > i {
    margin-top: 0.15rem;
    color: var(--p-primary-color);
}

.selected-point-card strong,
.selected-point-card span,
.selected-point-card small {
    display: block;
}

.selected-point-card span,
.selected-point-card small {
    color: var(--p-text-muted-color);
    font-size: 0.78rem;
}

.no-selected-points {
    min-height: 210px;
    border-style: dashed !important;
    background: var(--p-surface-50);
}

.no-selected-points > span {
    margin-bottom: 0.45rem;
    color: var(--p-text-muted-color);
}

.no-selected-points-icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin-bottom: 0.25rem;
    border-radius: 50%;
    color: var(--p-primary-color);
    background: var(--p-primary-50);
}

.no-selected-points-icon i {
    font-size: 1.35rem;
}

:deep(.point-table .p-datatable-table) {
    min-width: 920px;
}

.source-option {
    min-height: 86px;
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition: border-color 0.2s, background 0.2s, transform 0.2s;
}

.source-option:hover,
.source-option.active {
    border-color: var(--p-primary-color);
    background: var(--p-primary-50);
}

.source-option:hover {
    transform: translateY(-1px);
}

.source-option > i {
    color: var(--p-primary-color);
    font-size: 1.25rem;
}

.source-option span,
.source-option strong,
.source-option small {
    display: block;
}

.source-option small {
    margin-top: 0.15rem;
    color: var(--p-text-muted-color);
}

.library-selector {
    background: var(--p-surface-50);
}

.upload-actions {
    border-style: dashed !important;
    background: var(--p-surface-50);
}

.library-thumb {
    display: grid;
    place-items: center;
    width: 52px;
    height: 38px;
    overflow: hidden;
    flex: 0 0 52px;
    border-radius: 6px;
    background: var(--p-surface-100);
}

.library-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.plan-icon + div strong,
.plan-icon + div span {
    display: block;
}

.plan-icon + div strong {
    font-size: 1.05rem;
}

.plan-icon + div span {
    color: var(--p-text-muted-color);
    font-size: 0.85rem;
}

.plan-eyebrow {
    display: block;
    margin-bottom: 0.1rem;
    color: var(--p-primary-color);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.plan-icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 10px;
    color: var(--p-primary-color);
    background: var(--p-primary-50);
}

.plan-icon i {
    font-size: 1.25rem;
}

.plan-detail-item {
    min-width: 0;
    border-color: color-mix(in srgb, var(--p-primary-color) 18%, transparent) !important;
    background: color-mix(in srgb, var(--p-content-background) 72%, transparent);
}

.plan-detail-item > i {
    margin-top: 0.15rem;
    color: var(--p-primary-color);
}

.plan-detail-item div {
    min-width: 0;
}

.plan-detail-item small,
.plan-detail-item strong,
.plan-detail-item span {
    display: block;
}

.plan-detail-item small {
    color: var(--p-text-muted-color);
    font-size: 0.72rem;
}

.plan-detail-item strong {
    margin: 0.08rem 0;
    font-size: 0.9rem;
}

.plan-detail-item span {
    overflow: hidden;
    color: var(--p-text-muted-color);
    font-size: 0.72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.plan-price strong {
    color: var(--p-primary-color);
}

.media-image img {
    display: block;
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.video-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 10px;
    color: var(--p-primary-color);
    background: var(--p-primary-50);
}

.video-icon i {
    font-size: 1.4rem;
}

.media-order-thumb {
    width: 52px;
    height: 38px;
    flex: 0 0 52px;
    background: var(--p-surface-100);
    object-fit: cover;
}

.media-order-item {
    transition: border-color 0.2s, background 0.2s, opacity 0.2s;
}

.media-order-item.dragging {
    opacity: 0.45;
}

.media-order-item.drag-over {
    border-color: var(--p-primary-color) !important;
    background: var(--p-primary-50);
}

.media-drag-handle {
    padding: 0.65rem 0.4rem;
    color: var(--p-text-muted-color);
    cursor: grab;
}

.media-drag-handle:active {
    cursor: grabbing;
}
</style>
