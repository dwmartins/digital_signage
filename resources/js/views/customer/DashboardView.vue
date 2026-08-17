<script setup>
import Spinner from "@/components/shared/Spinner.vue";
import { showAlert } from "@/helpers/alert";
import customerDashboardService from "@/services/customer-dashboard.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);
const hasActiveSubscription = ref(false);

const firstName = computed(() => auth.getUser().name ?? "Anunciante");

const fetchDashboard = async () => {
    try {
        loading.value = true;
        const response = await customerDashboardService.index();
        hasActiveSubscription.value = Boolean(response.has_active_subscription);
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDashboard);
</script>

<template>
    <section class="container customer-dashboard py-3 py-md-4">
        <div class="mb-4">
            <span class="text-primary fw-semibold small">VISÃO GERAL</span>
            <h1 class="h3 fw-bold mt-1 mb-1">Olá, {{ firstName }}!</h1>
            <p class="text-muted mb-0">Acompanhe suas campanhas e conteúdos em um só lugar.</p>
        </div>

        <div v-if="loading" class="d-flex justify-content-center align-items-center dashboard-state">
            <Spinner />
        </div>

        <div
            v-else-if="!hasActiveSubscription"
            class="dashboard-state d-flex align-items-center justify-content-center"
        >
            <div class="subscription-empty position-relative overflow-hidden text-center w-100 rounded-4 shadow-sm p-4 p-md-5">
                <div class="decoration decoration-left"></div>
                <div class="decoration decoration-right"></div>

                <div class="empty-content position-relative mx-auto">
                    <div class="empty-icon d-inline-grid align-items-center justify-content-center rounded-circle mb-4">
                        <i class="pi pi-megaphone"></i>
                    </div>

                    <div class="d-flex justify-content-center mb-3">
                        <span class="badge d-inline-flex align-items-center justify-content-center rounded-pill text-bg-light border px-3 py-2 text-center">
                            Sua marca merece ser vista
                        </span>
                    </div>

                    <h2 class="fw-bold mb-3">Comece sua primeira campanha</h2>
                    <p class="text-muted mx-auto mb-4">
                        Você ainda não possui uma assinatura ativa. Escolha o plano ideal e leve sua mensagem para os melhores pontos de exibição.
                    </p>

                    <Button
                        label="Começar agora"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        size="large"
                        class="start-button px-4"
                        @click="router.push({ name: 'customer.campaign-onboarding' })"
                    />

                    <div class="benefits d-flex flex-wrap justify-content-center gap-3 gap-md-4 mt-4">
                        <span><i class="pi pi-check-circle me-2"></i>Configuração simples</span>
                        <span><i class="pi pi-check-circle me-2"></i>Conteúdo sob controle</span>
                        <span><i class="pi pi-check-circle me-2"></i>Maior visibilidade</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="dashboard-state"></div>
    </section>
</template>

<style scoped>
.customer-dashboard {
    min-height: calc(100vh - 5rem);
}

.dashboard-state {
    min-height: 60vh;
}

.subscription-empty {
    border: 1px solid var(--p-content-border-color);
    background:
        radial-gradient(circle at top, color-mix(in srgb, var(--p-primary-color) 12%, transparent), transparent 48%),
        var(--p-content-background);
}

.empty-content {
    max-width: 720px;
    z-index: 1;
}

.empty-icon {
    width: 5.5rem;
    height: 5.5rem;
    color: #ffffff;
    background: linear-gradient(135deg, var(--p-primary-color), #9b5cff);
    box-shadow: 0 1rem 2rem color-mix(in srgb, var(--p-primary-color) 28%, transparent);
}

.empty-icon i {
    font-size: 2.2rem;
}

.empty-content h2 {
    font-size: clamp(1.6rem, 4vw, 2.35rem);
}

.empty-content p {
    max-width: 600px;
    line-height: 1.7;
}

.start-button {
    box-shadow: 0 0.75rem 1.5rem color-mix(in srgb, var(--p-primary-color) 24%, transparent);
}

.benefits span {
    color: var(--p-text-muted-color);
    font-size: 0.875rem;
}

.benefits i {
    color: var(--p-primary-color);
}

.decoration {
    position: absolute;
    width: 12rem;
    height: 12rem;
    border-radius: 50%;
    filter: blur(1px);
    opacity: 0.12;
    background: var(--p-primary-color);
}

.decoration-left {
    left: -6rem;
    bottom: -7rem;
}

.decoration-right {
    top: -7rem;
    right: -6rem;
}

@media (max-width: 575.98px) {
    .dashboard-state {
        min-height: 65vh;
    }

    .subscription-empty {
        padding-block: 2.5rem !important;
    }

    .empty-icon {
        width: 4.5rem;
        height: 4.5rem;
    }

    .empty-icon i {
        font-size: 1.8rem;
    }

    .benefits {
        flex-direction: column;
    }
}
</style>
