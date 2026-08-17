<script setup>
import AlertBox from "@/components/shared/AlertBox.vue";
import { computed, onBeforeUnmount, ref } from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    plan: {
        type: Object,
        required: true,
    },
    orientations: {
        type: Array,
        default: () => ["landscape"],
    },
    libraryMedia: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["add-files", "remove-file", "toggle-library", "reorder"]);
const input = ref();
const draggingIndex = ref(null);
const contentSource = ref("upload");
const previewVisible = ref(false);
const previewFile = ref(null);
const previewUrl = ref(null);
const previewOrientation = ref("landscape");
const fileUrls = new WeakMap();
const generatedUrls = new Set();

const accept = computed(() => props.plan.media_type === "video"
    ? "video/mp4,video/webm,video/quicktime"
    : "image/jpeg,image/png,image/webp");

const typeLabel = computed(() => props.plan.media_type === "video" ? "vídeos" : "imagens");
const availableOrientations = computed(() => [...new Set(
    props.orientations.length ? props.orientations : ["landscape"],
)]);
const compatibleLibrary = computed(() => props.libraryMedia.filter(
    (media) => media.type === props.plan.media_type,
));
const selectedItems = computed(() => props.form.media_order.map((key) => {
    if (key.startsWith("library:")) {
        const id = Number(key.replace("library:", ""));
        const media = compatibleLibrary.value.find((item) => item.id === id);

        return media ? { key, source: "library", media } : null;
    }

    const index = Number(key.replace("file:", ""));
    const file = props.form.files[index];

    return file ? { key, source: "file", file, fileIndex: index } : null;
}).filter(Boolean));
const selectedCount = computed(() => props.form.files.length + props.form.media_asset_ids.length);
const previewIsImage = computed(() => previewFile.value
    && (previewFile.value.type === "image" || previewFile.value.type.startsWith("image/")));

const chooseFiles = () => input.value?.click();

const selected = (event) => {
    emit("add-files", Array.from(event.target.files ?? []));
    event.target.value = "";
};

const dropped = (targetIndex) => {
    if (draggingIndex.value !== null && draggingIndex.value !== targetIndex) {
        emit("reorder", draggingIndex.value, targetIndex);
    }

    draggingIndex.value = null;
};

const fileSize = (bytes) => {
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const fileUrl = (file) => {
    if (file.preview_url) return file.preview_url;

    if (!fileUrls.has(file)) {
        const url = URL.createObjectURL(file);
        fileUrls.set(file, url);
        generatedUrls.add(url);
    }

    return fileUrls.get(file);
};

const openPreview = (file) => {
    previewFile.value = file;
    previewUrl.value = fileUrl(file);
    previewOrientation.value = availableOrientations.value[0] ?? "landscape";
    previewVisible.value = true;
};

const removeItem = (item) => {
    if (item.source === "library") {
        emit("toggle-library", item.media.id);
        return;
    }

    emit("remove-file", item.fileIndex);
};

onBeforeUnmount(() => generatedUrls.forEach((url) => URL.revokeObjectURL(url)));
</script>

<template>
    <div>
        <div class="mb-4">
            <span class="step-eyebrow">ETAPA 4</span>
            <h2 class="h4 fw-bold mb-2">Adicione o conteúdo da campanha</h2>
            <p class="text-muted mb-0">
                Seu plano aceita até {{ plan.media_limit }} {{ typeLabel }}. Os arquivos passarão por análise antes da exibição.
            </p>
        </div>

        <AlertBox v-if="plan.media_type === 'video'" type="info" class="mb-4">
            Cada vídeo deve possuir no máximo <strong>15 segundos</strong> e até 100 MB.
        </AlertBox>

        <div class="source-selector d-grid d-md-flex gap-2 mb-4">
            <button
                type="button"
                class="source-button d-flex align-items-center gap-3 text-start rounded-3 p-3 flex-fill"
                :class="{ active: contentSource === 'upload' }"
                @click="contentSource = 'upload'"
            >
                <i class="pi pi-cloud-upload"></i>
                <span>
                    <strong class="d-block">Enviar novo arquivo</strong>
                    <small class="text-muted">Escolha no computador ou celular</small>
                </span>
            </button>
            <button
                type="button"
                class="source-button d-flex align-items-center gap-3 text-start rounded-3 p-3 flex-fill"
                :class="{ active: contentSource === 'library' }"
                @click="contentSource = 'library'"
            >
                <i class="pi pi-images"></i>
                <span>
                    <strong class="d-block">Usar minha Biblioteca</strong>
                    <small class="text-muted">Reutilize conteúdos já enviados</small>
                </span>
            </button>
        </div>

        <input
            ref="input"
            type="file"
            class="d-none"
            :accept="accept"
            :multiple="plan.media_limit > 1"
            @change="selected"
        />

        <button
            v-if="contentSource === 'upload' && selectedCount < plan.media_limit"
            type="button"
            class="upload-area w-100 rounded-4 text-center p-4 p-md-5"
            @click="chooseFiles"
        >
            <span class="upload-icon d-inline-grid align-items-center justify-content-center rounded-circle mb-3">
                <i class="pi pi-cloud-upload"></i>
            </span>
            <strong class="d-block fs-5 mb-1">Selecionar {{ typeLabel }}</strong>
            <span class="text-muted small">Clique para buscar no seu computador ou celular</span>
        </button>

        <div v-if="contentSource === 'library'" class="library-panel rounded-4 p-3 p-md-4">
            <div v-if="compatibleLibrary.length" class="row g-3">
                <div v-for="media in compatibleLibrary" :key="media.id" class="col-12 col-md-6">
                    <button
                        type="button"
                        class="library-card d-flex align-items-center gap-3 text-start w-100 rounded-3 p-3"
                        :class="{ selected: form.media_asset_ids.includes(media.id) }"
                        :disabled="!form.media_asset_ids.includes(media.id) && selectedCount >= plan.media_limit"
                        @click="$emit('toggle-library', media.id)"
                    >
                        <span class="library-thumb d-inline-grid align-items-center justify-content-center rounded-3 overflow-hidden flex-shrink-0">
                            <img v-if="media.type === 'image'" :src="media.preview_url" :alt="media.name" />
                            <i v-else class="pi pi-video"></i>
                        </span>
                        <span class="min-w-0 flex-grow-1">
                            <strong class="d-block text-truncate">{{ media.name }}</strong>
                            <small class="d-block text-muted text-truncate">{{ media.original_name }}</small>
                            <Tag
                                :value="media.approval_status === 'approved' ? 'Aprovada' : 'Aguardando aprovação'"
                                :severity="media.approval_status === 'approved' ? 'success' : 'warn'"
                                class="mt-2"
                            />
                        </span>
                        <i
                            :class="form.media_asset_ids.includes(media.id) ? 'pi pi-check-circle text-primary' : 'pi pi-circle text-muted'"
                        ></i>
                    </button>
                </div>
            </div>
            <div v-else class="text-center py-5">
                <i class="pi pi-images fs-2 text-muted"></i>
                <p class="text-muted mt-3 mb-0">Você ainda não possui {{ typeLabel }} compatíveis na Biblioteca.</p>
            </div>
        </div>

        <div v-if="selectedItems.length" class="mt-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <strong>{{ selectedCount }} de {{ plan.media_limit }} mídia(s)</strong>
                <Tag
                    :value="form.playback_mode === 'random' ? 'Ordem aleatória' : 'Ordem definida por você'"
                    :icon="form.playback_mode === 'random' ? 'pi pi-sync' : 'pi pi-sort-alt'"
                    severity="secondary"
                />
            </div>

            <div
                v-if="plan.media_limit > 1"
                class="playback-panel d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 rounded-3 p-3 mb-3"
            >
                <div>
                    <strong class="d-block">Como as mídias devem aparecer?</strong>
                    <small class="text-muted">Você pode definir a sequência ou deixar a plataforma alternar.</small>
                </div>
                <SelectButton
                    v-model="form.playback_mode"
                    :options="[
                        { label: 'Na minha ordem', value: 'sequential' },
                        { label: 'Aleatoriamente', value: 'random' },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    :allowEmpty="false"
                />
            </div>

            <AlertBox type="warning" class="mb-3">
                Clique no ícone de olho para visualizar como sua mídia ficará no ponto de exibição. Você poderá alterá-la futuramente.
            </AlertBox>

            <div class="d-grid gap-2">
                <div
                    v-for="(item, index) in selectedItems"
                    :key="item.key"
                    class="media-item d-flex align-items-center gap-3 rounded-3 p-3"
                    :draggable="form.playback_mode === 'sequential'"
                    @dragstart="draggingIndex = index"
                    @dragover.prevent
                    @drop="dropped(index)"
                >
                    <span class="file-preview d-inline-grid align-items-center justify-content-center rounded-3 flex-shrink-0 overflow-hidden">
                        <img v-if="plan.media_type === 'image'" :src="fileUrl(item.file || item.media)" :alt="item.file?.name || item.media?.name" />
                        <i v-else class="pi pi-video"></i>
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <strong class="d-block text-truncate">{{ index + 1 }}. {{ item.file?.name || item.media?.name }}</strong>
                        <small class="text-muted">
                            {{ fileSize(item.file?.size ?? item.media?.size_bytes) }} · {{ item.source === "library" ? "Biblioteca" : "Novo arquivo" }}
                        </small>
                    </div>
                    <div class="media-actions d-flex align-items-center flex-shrink-0">
                        <i
                            v-if="form.playback_mode === 'sequential' && selectedItems.length > 1"
                            class="pi pi-bars drag-handle text-muted mx-2"
                        ></i>
                        <Button
                            icon="pi pi-eye"
                            severity="secondary"
                            variant="text"
                            rounded
                            aria-label="Pré-visualizar mídia"
                            @click="openPreview(item.file || item.media)"
                        />
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            variant="text"
                            rounded
                            aria-label="Remover arquivo"
                            @click="removeItem(item)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="previewVisible"
            modal
            header="Pré-visualização da mídia"
            :style="{ width: 'min(94vw, 920px)' }"
        >
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div class="min-w-0">
                    <strong class="d-block text-truncate">{{ previewFile?.name }}</strong>
                    <small class="text-muted">
                        Esta é uma simulação. O resultado pode variar conforme o equipamento instalado.
                    </small>
                </div>

                <SelectButton
                    v-model="previewOrientation"
                    :options="[
                        { label: 'Horizontal', value: 'landscape', icon: 'pi pi-desktop' },
                        { label: 'Vertical', value: 'portrait', icon: 'pi pi-mobile' },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    :allowEmpty="false"
                >
                    <template #option="slotProps">
                        <i :class="slotProps.option.icon" class="me-2"></i>
                        <span>{{ slotProps.option.label }}</span>
                    </template>
                </SelectButton>
            </div>

            <div class="preview-stage d-flex align-items-center justify-content-center rounded-4 p-3 p-md-4">
                <div class="screen-frame" :class="previewOrientation">
                    <div class="screen-camera"></div>
                    <div class="screen-content">
                        <img
                            v-if="previewIsImage"
                            :src="previewUrl"
                            :alt="previewFile?.name"
                        />
                        <video
                            v-else-if="previewUrl"
                            :src="previewUrl"
                            controls
                            playsinline
                        ></video>
                    </div>
                    <div class="screen-brand">PREVIEW</div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.step-eyebrow {
    color: var(--p-primary-color);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.source-button {
    border: 1px solid var(--p-content-border-color);
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;
}

.source-button:hover,
.source-button.active {
    border-color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 5%, var(--p-content-background));
}

.source-button.active {
    box-shadow: 0 0 0 1px var(--p-primary-color);
}

.source-button > i {
    color: var(--p-primary-color);
    font-size: 1.4rem;
}

.library-panel {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-surface-50);
}

.library-card {
    border: 1px solid var(--p-content-border-color);
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.2s ease;
}

.library-card:hover:not(:disabled),
.library-card.selected {
    border-color: var(--p-primary-color);
    box-shadow: 0 0 0 1px var(--p-primary-color);
}

.library-card:hover:not(:disabled) {
    transform: translateY(-1px);
}

.library-card:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.library-thumb {
    width: 4rem;
    height: 3.25rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.library-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.library-thumb i {
    font-size: 1.35rem;
}

.upload-area {
    border: 2px dashed var(--p-content-border-color);
    color: var(--p-text-color);
    background: var(--p-surface-50);
    transition:
        border-color 0.2s ease,
        background 0.2s ease;
}

.upload-area:hover {
    border-color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 5%, var(--p-surface-50));
}

.upload-icon {
    width: 4rem;
    height: 4rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.upload-icon i {
    font-size: 1.5rem;
}

.playback-panel,
.media-item {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-content-background);
}

.file-preview {
    width: 3.5rem;
    height: 3.5rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.file-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-preview i {
    font-size: 1.25rem;
}

.min-w-0 {
    min-width: 0;
}

.drag-handle {
    cursor: grab;
}

.preview-stage {
    min-height: 32rem;
    /* background:
        radial-gradient(circle at center, rgba(255, 255, 255, 0.08), transparent 60%),
        #20242b; */
}

.screen-frame {
    position: relative;
    max-width: 100%;
    border: 0.75rem solid #111418;
    border-bottom-width: 1.25rem;
    border-radius: 0.75rem;
    background: #08090b;
    box-shadow:
        0 1.5rem 3rem rgba(0, 0, 0, 0.4),
        inset 0 0 0 1px rgba(255, 255, 255, 0.08);
    transition:
        width 0.25s ease,
        aspect-ratio 0.25s ease;
}

.screen-frame.landscape {
    width: 46rem;
    aspect-ratio: 16 / 9;
}

.screen-frame.portrait {
    width: 19rem;
    aspect-ratio: 9 / 16;
}

.screen-content {
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #000000;
}

.screen-content img,
.screen-content video {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.screen-camera {
    position: absolute;
    top: -0.48rem;
    left: 50%;
    width: 0.25rem;
    height: 0.25rem;
    border-radius: 50%;
    background: #39414b;
    transform: translateX(-50%);
}

.screen-brand {
    position: absolute;
    right: 0;
    bottom: -1rem;
    left: 0;
    color: #69717c;
    font-size: 0.5rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-align: center;
}

@media (max-width: 575.98px) {
    .source-button {
        width: 100%;
        min-width: 0;
    }

    .library-panel {
        padding: 0.75rem !important;
    }

    .library-card {
        min-width: 0;
        padding: 0.75rem !important;
    }

    .library-thumb {
        width: 3.25rem;
        height: 3.25rem;
    }

    .playback-panel {
        min-width: 0;
    }

    .playback-panel :deep(.p-selectbutton) {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        width: 100%;
    }

    .playback-panel :deep(.p-togglebutton) {
        min-width: 0;
        padding-inline: 0.5rem;
    }

    .playback-panel :deep(.p-togglebutton-label) {
        overflow: hidden;
        font-size: 0.8rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .media-item {
        display: grid !important;
        grid-template-columns: 3rem minmax(0, 1fr);
        align-items: center;
        gap: 0.5rem !important;
        padding-inline: 0.65rem !important;
    }

    .media-item .min-w-0 {
        width: 100%;
    }

    .media-actions {
        grid-column: 1 / -1;
        width: auto;
        justify-content: flex-end;
        border-top: 1px solid var(--p-content-border-color);
        padding-top: 0.35rem;
    }

    .file-preview {
        width: 3rem;
        height: 3rem;
    }

    .preview-stage {
        min-height: 25rem;
    }
}
</style>
