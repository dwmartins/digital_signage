<script setup>
import PlanFormDialog from "@/components/dialog/plan/PlanFormDialog.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import EmptyData from "@/components/shared/EmptyData.vue";
import { showAlert } from "@/helpers/alert";
import planService from "@/services/plan.service";
import { useAuthStore } from "@/stores/authStore";
import { computed, onMounted, ref } from "vue";

const auth = useAuthStore();
const plans = ref([]);
const plan = ref(null);
const loading = ref(false);
const form = ref(false);

const canCreate = computed(() => auth.hasPermission("plans.create"));
const canUpdate = computed(() => auth.hasPermission("plans.update"));
const canDelete = computed(() => auth.hasPermission("plans.delete"));

const money = (value) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value ?? 0);

const fetchAll = async () => {
    try {
        loading.value = true;
        plans.value = (await planService.index()).data ?? [];
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        loading.value = false;
    }
};

const open = (data) => {
    if (!(data ? canUpdate.value : canCreate.value))
        return showAlert(
            "warning",
            "Você não possui permissão para esta ação.",
        );
    plan.value = data ? { ...data } : null;
    form.value = true;
};

const remove = async (data) => {
    if (!canDelete.value) return;
    if (!confirm(`Excluir o plano ${data.name}?`)) return;
    try {
        showAlert("success", (await planService.destroy(data.id)).message);
        fetchAll();
    } catch (error) {
        showAlert("error", error.response?.data);
    }
};

onMounted(fetchAll);

</script>
<template>
    <section class="container">
        <div
            class="d-flex justify-content-between align-items-start gap-3 mb-4"
        >
            <Breadcrumb
                :items="[{ icon: 'pi pi-home', to: '/' }, { label: 'Planos' }]"
            /><Button
                label="Novo plano"
                icon="pi pi-plus"
                size="small"
                :disabled="!canCreate"
                @click="open()"
            />
        </div>
        <div v-if="loading" class="row g-4">
            <div v-for="item in 3" :key="item" class="col-md-6 col-xl-4">
                <Skeleton height="25rem" borderRadius="16px" />
            </div>
        </div>
        <div v-else-if="plans.length" class="row g-4">
            <div v-for="item in plans" :key="item.id" class="col-md-6 col-xl-4">
                <article class="plan-card h-100">
                    <div
                        class="d-flex justify-content-between align-items-start gap-3"
                    >
                        <div class="plan-icon">
                            <i class="pi pi-sparkles"></i>
                        </div>
                        <Tag
                            :value="
                                item.status === 'active' ? 'Ativo' : 'Inativo'
                            "
                            :severity="
                                item.status === 'active'
                                    ? 'success'
                                    : 'secondary'
                            "
                        />
                    </div>
                    <div class="mt-4">
                        <small
                            class="text-primary fw-semibold text-uppercase"
                            >{{
                                item.billing_cycle === "yearly"
                                    ? "Plano anual"
                                    : "Plano mensal"
                            }}</small
                        >
                        <h3 class="mt-1 mb-2">{{ item.name }}</h3>
                        <p class="text-muted plan-description">
                            {{
                                item.description ||
                                "Plano comercial para campanhas digitais."
                            }}
                        </p>
                    </div>
                    <div class="plan-price my-4">
                        <strong>{{ money(item.price) }}</strong
                        ><span
                            >/
                            {{
                                item.billing_cycle === "yearly" ? "ano" : "mês"
                            }}</span
                        >
                    </div>
                    <div class="plan-features">
                        <div>
                            <i class="pi pi-desktop"></i
                            ><span
                                >Até <strong>{{ item.screen_limit }}</strong>
                                {{
                                    item.screen_limit === 1 ? "tela" : "telas"
                                }}</span
                            >
                        </div>
                        <div>
                            <i
                                :class="
                                    item.media_type === 'video'
                                        ? 'pi pi-video'
                                        : 'pi pi-image'
                                "
                            ></i
                            ><span
                                >Mídia em
                                <strong>{{
                                    item.media_type === "video"
                                        ? "vídeo"
                                        : "imagem"
                                }}</strong></span
                            >
                        </div>
                        <div>
                            <i class="pi pi-link"></i
                            ><span
                                ><strong>{{ item.subscriptions_count }}</strong>
                                assinaturas vinculadas</span
                            >
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-auto pt-4">
                        <Button
                            label="Editar"
                            icon="pi pi-pencil"
                            outlined
                            class="flex-grow-1"
                            :disabled="!canUpdate"
                            @click="open(item)"
                        /><Button
                            icon="pi pi-trash"
                            severity="danger"
                            outlined
                            :disabled="
                                !canDelete || item.subscriptions_count > 0
                            "
                            @click="remove(item)"
                        />
                    </div>
                </article>
            </div>
        </div>
        <EmptyData v-else :show-btn-clean-filters="false" />
        <PlanFormDialog v-model="form" :plan="plan" @saved="fetchAll" />
    </section>
</template>
<style scoped>
.plan-card {
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    background: linear-gradient(
        145deg,
        var(--p-surface-0),
        var(--p-surface-50)
    );
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    transition: 0.2s ease;
}
.plan-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.11);
    border-color: var(--p-primary-300);
}
.plan-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    color: var(--p-primary-color);
    background: var(--p-primary-50);
    font-size: 1.25rem;
}
.plan-description {
    min-height: 48px;
}
.plan-price strong {
    font-size: 2rem;
}
.plan-price span {
    color: var(--p-text-muted-color);
}
.plan-features {
    display: grid;
    gap: 0.9rem;
    padding: 1rem 0;
    border-top: 1px solid var(--p-surface-200);
    border-bottom: 1px solid var(--p-surface-200);
}
.plan-features div {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.plan-features i {
    color: var(--p-primary-color);
}
</style>
