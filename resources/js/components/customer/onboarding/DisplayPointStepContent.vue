<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    points: {
        type: Array,
        default: () => [],
    },
    selectedIds: {
        type: Array,
        default: () => [],
    },
    limit: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["toggle"]);
const search = ref("");
const first = ref(0);
const perPage = 4;

const filteredPoints = computed(() => {
    const term = search.value.trim().toLocaleLowerCase("pt-BR");

    if (!term) return props.points;

    return props.points.filter((point) => [
        point.name,
        point.location,
        point.establishment?.name,
        point.establishment?.city?.name,
        point.establishment?.neighborhood?.name,
        point.establishment?.city?.state?.code,
    ].some((value) => String(value ?? "").toLocaleLowerCase("pt-BR").includes(term)));
});

const paginatedPoints = computed(() => filteredPoints.value.slice(
    first.value,
    first.value + perPage,
));

watch(search, () => {
    first.value = 0;
});

const isSelected = (id) => props.selectedIds.includes(id);
const isDisabled = (id) => !isSelected(id) && props.selectedIds.length >= props.limit;

const location = (point) => [
    point.establishment?.neighborhood?.name,
    point.establishment?.city?.name,
    point.establishment?.city?.state?.code,
].filter(Boolean).join(" · ");

const displayFormat = (orientation) => orientation === "portrait"
    ? {
        label: "Vertical",
        icon: "pi pi-mobile",
        ratio: "9:16",
        resolution: "1080 × 1920 px",
    }
    : {
        label: "Horizontal",
        icon: "pi pi-desktop",
        ratio: "16:9",
        resolution: "1920 × 1080 px",
    };
</script>

<template>
    <div>
        <div class="mb-4">
            <span class="step-eyebrow">ETAPA 3</span>
            <h2 class="h4 fw-bold mb-2">Onde sua campanha será exibida?</h2>
            <p class="text-muted mb-0">
                Selecione até {{ limit }} ponto(s). A orientação indicada ajuda você a preparar o conteúdo corretamente.
            </p>
        </div>

        <div class="row align-items-end g-3 mb-4">
            <div class="col-12 col-lg-8">
                <div class="field mb-0">
                    <label>Buscar ponto de exibição</label>
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="search"
                            placeholder="Estabelecimento, local, cidade ou bairro"
                            fluid
                        />
                    </IconField>
                </div>
            </div>
            <div class="col-12 col-lg-4 text-lg-end">
                <Tag
                    :value="`${selectedIds.length} de ${limit} selecionado(s)`"
                    icon="pi pi-map-marker"
                    severity="secondary"
                    class="px-3 py-2"
                />
            </div>
        </div>

        <div v-if="filteredPoints.length" class="row g-3">
            <div v-for="point in paginatedPoints" :key="point.id" class="col-12 col-xl-6">
                <button
                    type="button"
                    class="point-card d-flex align-items-start gap-3 text-start w-100 h-100 rounded-3 p-3"
                    :class="{ selected: isSelected(point.id) }"
                    :disabled="isDisabled(point.id)"
                    @click="$emit('toggle', point.id)"
                >
                    <span class="point-icon d-inline-grid align-items-center justify-content-center rounded-3 flex-shrink-0">
                        <i class="pi pi-map-marker"></i>
                    </span>
                    <span class="min-w-0 flex-grow-1">
                        <span class="d-flex align-items-start justify-content-between gap-2">
                            <strong>{{ point.establishment?.name }}</strong>
                            <i
                                :class="isSelected(point.id) ? 'pi pi-check-circle text-primary' : 'pi pi-circle text-muted'"
                            ></i>
                        </span>
                        <span class="d-block fw-semibold mt-1">{{ point.name }}</span>
                        <small v-if="point.location" class="d-block text-muted mt-1">{{ point.location }}</small>
                        <small class="d-block text-muted mt-1">{{ location(point) }}</small>
                        <span class="d-flex flex-wrap gap-2 mt-3">
                            <Tag
                                :value="displayFormat(point.orientation).label"
                                :icon="displayFormat(point.orientation).icon"
                                severity="secondary"
                            />
                            <Tag
                                :value="`Proporção ${displayFormat(point.orientation).ratio}`"
                                icon="pi pi-expand"
                                severity="secondary"
                            />
                        </span>
                        <small class="resolution-hint d-flex align-items-center gap-2 mt-2">
                            <i class="pi pi-image"></i>
                            Recomendado: {{ displayFormat(point.orientation).resolution }}
                        </small>
                    </span>
                </button>
            </div>
        </div>

        <Paginator
            v-if="filteredPoints.length > perPage"
            v-model:first="first"
            :rows="perPage"
            :totalRecords="filteredPoints.length"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            class="mt-4"
        />

        <div v-if="!filteredPoints.length" class="text-center border rounded-4 p-5">
            <i class="pi pi-map-marker fs-2 text-muted"></i>
            <p class="text-muted mt-3 mb-0">Nenhum ponto de exibição foi encontrado.</p>
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

.point-card {
    border: 1px solid var(--p-content-border-color);
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        transform 0.2s ease;
}

.point-card:not(:disabled):hover {
    border-color: var(--p-primary-color);
    transform: translateY(-1px);
}

.point-card.selected {
    border-color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 5%, var(--p-content-background));
}

.point-card:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.point-icon {
    width: 2.75rem;
    height: 2.75rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.resolution-hint {
    color: var(--p-primary-color);
    font-weight: 600;
}

.min-w-0 {
    min-width: 0;
}
</style>
