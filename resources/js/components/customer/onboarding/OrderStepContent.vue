<script setup>
import AlertBox from "@/components/shared/AlertBox.vue";
import { computed, onBeforeUnmount, ref } from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    displayPoints: {
        type: Array,
        default: () => [],
    },
    libraryMedia: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["reorder"]);
const dragging = ref(null);
const fileUrls = new WeakMap();
const generatedUrls = new Set();

const establishments = computed(() => {
    const groups = new Map();

    props.displayPoints.forEach((point) => {
        if (!(props.form.display_orders[point.id]?.length > 0)) return;

        const establishmentId = point.establishment?.id ?? point.establishment_id;

        if (!groups.has(establishmentId)) {
            groups.set(establishmentId, {
                id: establishmentId,
                name: point.establishment?.name ?? "Estabelecimento",
                points: [],
            });
        }

        groups.get(establishmentId).points.push(point);
    });

    return [...groups.values()];
});

const pointsWithoutMedia = computed(() => props.displayPoints.filter(
    (point) => !(props.form.display_orders[point.id]?.length > 0),
).length);

const mediaByKey = (key) => {
    if (key.startsWith("library:")) {
        const id = Number(key.replace("library:", ""));
        const media = props.libraryMedia.find((item) => item.id === id);

        return media ? { key, media, name: media.name, type: media.type } : null;
    }

    const index = Number(key.replace("file:", ""));
    const file = props.form.files[index];

    return file ? {
        key,
        media: file,
        name: file.name,
        type: file.type.startsWith("image/") ? "image" : "video",
    } : null;
};

const pointMedia = (pointId) => (props.form.display_orders[pointId] ?? [])
    .map(mediaByKey)
    .filter(Boolean);

const positionLabel = (index) => `${index + 1}º`;

const mediaUrl = (media) => {
    if (media.preview_url) return media.preview_url;

    if (!fileUrls.has(media)) {
        const url = URL.createObjectURL(media);
        fileUrls.set(media, url);
        generatedUrls.add(url);
    }

    return fileUrls.get(media);
};

const dropped = (pointId, targetIndex) => {
    if (dragging.value && dragging.value.pointId === pointId && dragging.value.index !== targetIndex) {
        emit("reorder", pointId, dragging.value.index, targetIndex);
    }

    dragging.value = null;
};

onBeforeUnmount(() => generatedUrls.forEach((url) => URL.revokeObjectURL(url)));
</script>

<template>
    <div>
        <div class="mb-4">
            <span class="step-eyebrow">ETAPA 5</span>
            <h2 class="h4 fw-bold mb-2">Defina a ordem em cada local</h2>
            <p class="text-muted mb-0">
                Organize as mídias separadamente para cada ponto de exibição.
            </p>
        </div>

        <AlertBox type="info" class="mb-4">
            Arraste as mídias ou use as setas para alterar a sequência. A primeira da lista será exibida antes das demais naquele ponto.
        </AlertBox>

        <AlertBox v-if="pointsWithoutMedia > 0" type="warning" class="mb-4">
            Você selecionou
            <strong>
                {{ displayPoints.length }}
                {{ displayPoints.length === 1 ? "ponto de exibição" : "pontos de exibição" }}
            </strong> e
            <strong>{{ pointsWithoutMedia }} {{ pointsWithoutMedia === 1 ? "está" : "estão" }} sem mídia</strong>
            para exibição.
        </AlertBox>

        <div class="d-grid gap-4">
            <section v-for="establishment in establishments" :key="establishment.id" class="establishment-group rounded-4 p-3 p-md-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="establishment-icon d-inline-grid align-items-center justify-content-center rounded-3 flex-shrink-0">
                        <i class="pi pi-building"></i>
                    </span>
                    <div>
                        <small class="text-muted">Estabelecimento</small>
                        <h3 class="h6 fw-bold mb-0 mt-1">{{ establishment.name }}</h3>
                    </div>
                </div>

                <div class="row g-3">
                    <div v-for="point in establishment.points" :key="point.id" class="col-12 col-xl-6">
                        <div class="point-order h-100 rounded-3 p-3">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div class="min-w-0">
                                    <strong class="d-block text-truncate">{{ point.name }}</strong>
                                    <small class="text-muted">{{ point.location }}</small>
                                </div>
                                <Tag
                                    :value="point.orientation === 'portrait' ? 'Vertical' : 'Horizontal'"
                                    :icon="point.orientation === 'portrait' ? 'pi pi-mobile' : 'pi pi-desktop'"
                                    severity="secondary"
                                />
                            </div>

                            <div v-if="pointMedia(point.id).length > 1" class="d-grid gap-2">
                                <div
                                    v-for="(item, index) in pointMedia(point.id)"
                                    :key="item.key"
                                    class="order-media d-flex align-items-center gap-3 rounded-3 p-2"
                                    draggable="true"
                                    @dragstart="dragging = { pointId: point.id, index }"
                                    @dragover.prevent
                                    @drop="dropped(point.id, index)"
                                >
                                    <span class="position-number d-inline-grid align-items-center justify-content-center rounded-pill flex-shrink-0 px-2">
                                        {{ positionLabel(index) }}
                                    </span>
                                    <span class="media-thumb d-inline-grid align-items-center justify-content-center rounded-2 overflow-hidden flex-shrink-0">
                                        <img v-if="item.type === 'image'" :src="mediaUrl(item.media)" :alt="item.name" />
                                        <i v-else class="pi pi-video"></i>
                                    </span>
                                    <strong
                                        class="media-name d-block text-truncate"
                                        v-tooltip.top="item.name"
                                    >
                                        {{ item.name }}
                                    </strong>
                                    <span class="order-actions d-flex align-items-center flex-shrink-0 gap-3">
                                        <Button
                                            icon="pi pi-arrow-up"
                                            severity="secondary"
                                            variant="text"
                                            rounded
                                            size="small"
                                            :disabled="index === 0"
                                            aria-label="Mover mídia para cima"
                                            @click="$emit('reorder', point.id, index, index - 1)"
                                        />
                                        <Button
                                            icon="pi pi-arrow-down"
                                            severity="secondary"
                                            variant="text"
                                            rounded
                                            size="small"
                                            :disabled="index === pointMedia(point.id).length - 1"
                                            aria-label="Mover mídia para baixo"
                                            @click="$emit( 'reorder', point.id, index, index + 1)"
                                        />
                                        <i class="pi pi-bars drag-handle text-muted ms-1"></i>
                                    </span>
                                </div>
                            </div>

                            <div v-else class="single-media d-flex align-items-center gap-2 rounded-3 p-3">
                                <i class="pi pi-check-circle text-success"></i>
                                <small class="text-muted">Este ponto possui apenas uma mídia; não é necessário ordenar.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.step-eyebrow {
    color: var(--p-primary-color);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.establishment-group {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-surface-50);
}

.establishment-icon {
    width: 2.75rem;
    height: 2.75rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.point-order,
.order-media,
.single-media {
    border: 1px solid var(--p-content-border-color);
    background: var(--p-content-background);
}

.order-media {
    cursor: grab;
    width: 100%;
    min-width: 0;
    overflow: hidden;
}

.order-media:active {
    cursor: grabbing;
}

.position-number {
    height: 1.75rem;
    min-width: 2.25rem;
    color: var(--p-primary-color);
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--p-primary-100);
}

.media-thumb {
    width: 2.75rem;
    height: 2.25rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.drag-handle {
    cursor: grab;
}

.media-name {
    flex: 1 1 0;
    width: 0;
    min-width: 0;
}

.order-actions {
    max-width: max-content;
    margin-left: auto;
}

.min-w-0 {
    min-width: 0;
}

@media (max-width: 575.98px) {
    .establishment-group {
        width: calc(100% + 1rem);
        margin-left: -1rem;
    }

    .order-media {
        display: grid !important;
        grid-template-columns: auto auto minmax(0, 1fr);
    }

    .media-name {
        width: 100%;
    }

    .order-actions {
        grid-column: 1 / -1;
        width: 100%;
        max-width: none;
        justify-content: flex-end;
        border-top: 1px solid var(--p-content-border-color);
        padding-top: 0.35rem;
        margin-left: 0;
    }

}
</style>
