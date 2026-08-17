<script setup>
defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    selectedId: {
        type: Number,
        default: null,
    },
});

defineEmits(["select"]);

const currency = (value) => Number(value).toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
});
</script>

<template>
    <div>
        <div class="step-heading mb-4">
            <span class="step-eyebrow">ETAPA 1</span>
            <h2 class="h4 fw-bold mb-2">Escolha o plano ideal para sua campanha</h2>
            <p class="text-muted mb-0">Você poderá revisar todos os dados antes de finalizar.</p>
        </div>

        <div v-if="plans.length" class="plan-scroll pe-1 pe-md-2">
            <div class="row g-3 mx-0">
                <div v-for="plan in plans" :key="plan.id" class="col-12 col-lg-6 col-xxl-4">
                    <button
                        type="button"
                        class="plan-card position-relative text-start w-100 h-100 rounded-4 p-4"
                        :class="{ selected: selectedId === plan.id }"
                        @click="$emit('select', plan.id)"
                    >
                        <i
                            v-if="selectedId === plan.id"
                            class="pi pi-check-circle selected-check"
                        ></i>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="plan-icon d-inline-grid align-items-center justify-content-center rounded-3">
                                <i :class="plan.media_type === 'video' ? 'pi pi-video' : 'pi pi-image'"></i>
                            </span>
                            <div>
                                <strong class="d-block fs-5">{{ plan.name }}</strong>
                                <small class="text-muted">
                                    {{ plan.media_type === "video" ? "Vídeos" : "Imagens" }}
                                </small>
                            </div>
                        </div>

                        <p class="text-muted small plan-description">{{ plan.description || "Plano completo para divulgar sua marca." }}</p>

                        <div class="d-flex align-items-end gap-1 my-4">
                            <strong class="plan-price">{{ currency(plan.price) }}</strong>
                            <span class="text-muted mb-1">/{{ plan.billing_cycle === "yearly" ? "ano" : "mês" }}</span>
                        </div>

                        <div class="d-grid gap-2 small">
                            <span><i class="pi pi-check text-primary me-2"></i>Até {{ plan.media_limit }} mídia(s)</span>
                            <span><i class="pi pi-check text-primary me-2"></i>Até {{ plan.screen_limit }} ponto(s) de exibição</span>
                            <span><i class="pi pi-check text-primary me-2"></i>Análise de conteúdo incluída</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="text-center border rounded-4 p-5">
            <i class="pi pi-inbox fs-2 text-muted"></i>
            <p class="text-muted mt-3 mb-0">Nenhum plano está disponível no momento.</p>
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

.plan-card {
    border: 1px solid var(--p-content-border-color);
    color: var(--p-text-color);
    background: var(--p-content-background);
    transition:
        border-color 0.2s ease,
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.plan-card:hover {
    border-color: var(--p-primary-color);
    transform: translateY(-2px);
}

.plan-card.selected {
    border: 2px solid var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 5%, var(--p-content-background));
    box-shadow: 0 0.75rem 2rem color-mix(in srgb, var(--p-primary-color) 12%, transparent);
}

.selected-check {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: var(--p-primary-color);
    font-size: 1.25rem;
}

.plan-icon {
    width: 3rem;
    height: 3rem;
    color: var(--p-primary-color);
    background: var(--p-primary-100);
}

.plan-description {
    min-height: 2.5rem;
}

.plan-price {
    color: var(--p-primary-color);
    font-size: 1.8rem;
}

.plan-scroll {
    max-height: 42rem;
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: var(--p-primary-300) transparent;
}

.plan-scroll::-webkit-scrollbar {
    width: 0.45rem;
}

.plan-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.plan-scroll::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: var(--p-primary-300);
}

@media (max-width: 767.98px) {
    .plan-scroll {
        max-height: 32rem;
        padding-right: 0.35rem !important;
    }
}
</style>
