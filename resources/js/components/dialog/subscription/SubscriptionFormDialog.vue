<script setup>
import { showAlert } from "@/helpers/alert";
import subscriptionService from "@/services/subscription.service";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    modelValue: Boolean,
    subscription: Object,
    campaigns: Array,
    plans: Array,
    statuses: Array,
});
const emit = defineEmits(["update:modelValue", "saved"]);
const saving = ref(false);
const hydrating = ref(false);
const form = reactive({
    id: null,
    campaign_id: null,
    plan_id: null,
    status: "pending",
    price: 0,
    notes: "",
    starts_at: null,
    ends_at: null,
});
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
const selectedPlan = computed(() =>
    props.plans.find((plan) => plan.id === form.plan_id),
);
const selectedCampaign = computed(() =>
    props.campaigns.find((campaign) => campaign.id === form.campaign_id),
);
const compatiblePlans = computed(() => {
    const mediaType = selectedCampaign.value?.media_assets?.[0]?.type;
    return mediaType
        ? props.plans.filter((plan) => plan.media_type === mediaType)
        : props.plans;
});
const selectableStatuses = computed(() => {
    const allowed = {
        pending: ["pending", "cancelled"],
        active: ["active", "expired", "cancelled"],
        expired: ["expired", "cancelled"],
        cancelled: ["cancelled"],
    }[props.subscription?.status] ?? ["pending"];

    return props.statuses.map((option) => ({
        ...option,
        disabled: !allowed.includes(option.value),
    }));
});
const cycleLabel = computed(() =>
    selectedPlan.value?.billing_cycle === "yearly" ? "Anual" : "Mensal",
);
const price = (value) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value ?? 0);

const parseDate = (value) =>
    value ? new Date(String(value).replace(" ", "T")) : null;

const calculateCycleEnd = () => {
    if (!form.starts_at || !selectedPlan.value) return;
    const end = new Date(form.starts_at);
    if (selectedPlan.value.billing_cycle === "yearly")
        end.setFullYear(end.getFullYear() + 1);
    else end.setDate(end.getDate() + 30);
    form.ends_at = end;
};

const submit = async () => {
    try {
        saving.value = true;
        const response = await subscriptionService.update(form);
        showAlert("success", response.message);
        emit("saved");
        visible.value = false;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(
    () => form.plan_id,
    () => {
        if (hydrating.value) return;
        form.price = Number(selectedPlan.value?.price ?? 0);
        calculateCycleEnd();
    },
);

watch(
    () => form.starts_at,
    () => {
        if (!hydrating.value) calculateCycleEnd();
    },
);

watch(
    () => props.modelValue,
    (opened) => {
        if (!opened) return;
        hydrating.value = true;
        const subscription = props.subscription;
        Object.assign(form, {
            id: subscription?.id ?? null,
            campaign_id: subscription?.campaign_id ?? null,
            plan_id: subscription?.plan_id ?? null,
            status: subscription?.status ?? "pending",
            price: Number(subscription?.price ?? 0),
            notes: subscription?.notes ?? "",
            starts_at: parseDate(subscription?.starts_at),
            ends_at: parseDate(subscription?.ends_at),
        });
        if (!form.starts_at) form.starts_at = new Date();
        if (!form.ends_at) calculateCycleEnd();
        setTimeout(() => (hydrating.value = false), 0);
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Editar assinatura"
        :style="{ width: '48rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
    >
        <form id="subscriptionForm" class="row g-4" @submit.prevent="submit">
            <div class="col-12">
                <Message severity="info" :closable="false"
                    >Para ativar uma assinatura pendente, use a ação
                    <strong>Aprovar</strong> na listagem. Assim a fatura e a
                    transação são geradas corretamente.</Message
                >
            </div>
            <div class="col-md-7">
                <div class="field">
                    <label>Campanha</label
                    ><Select
                        v-model="form.campaign_id"
                        :options="campaigns"
                        optionLabel="label"
                        optionValue="id"
                        disabled
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-5">
                <div class="field">
                    <label>Status</label
                    ><Select
                        v-model="form.status"
                        :options="selectableStatuses"
                        optionLabel="label"
                        optionValue="value"
                        optionDisabled="disabled"
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-7">
                <div class="field">
                    <label>Plano</label
                    ><Select
                        v-model="form.plan_id"
                        :options="compatiblePlans"
                        optionLabel="name"
                        optionValue="id"
                        :disabled="subscription?.status === 'cancelled'"
                        filter
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-5">
                <div class="subscription-summary">
                    <strong>{{ price(form.price) }}</strong
                    ><span>Ciclo {{ cycleLabel.toLowerCase() }}</span>
                </div>
            </div>
            <div class="col-md-5">
                <div class="field">
                    <label>Valor negociado</label>
                    <InputNumber
                        v-model="form.price"
                        mode="currency"
                        currency="BRL"
                        locale="pt-BR"
                        :min="0"
                        fluid
                    />
                    <small class="text-muted"
                        >Use R$ 0,00 para uma assinatura gratuita.</small
                    >
                </div>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label>Data inicial</label
                    ><DatePicker
                        v-model="form.starts_at"
                        dateFormat="dd/mm/yy"
                        showIcon
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label>Data final</label
                    ><DatePicker
                        v-model="form.ends_at"
                        dateFormat="dd/mm/yy"
                        :minDate="form.starts_at"
                        showIcon
                        fluid
                    />
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label>Observação</label
                    ><Textarea
                        v-model="form.notes"
                        rows="3"
                        maxlength="5000"
                        autoResize
                        fluid
                    />
                </div>
            </div>
            <div class="col-12">
                <small class="text-muted"
                    ><i class="pi pi-info-circle me-1"></i>Ao alterar o início
                    ou o plano, a data final é sugerida pelo ciclo
                    {{ cycleLabel.toLowerCase() }}: 30 dias para mensal ou um
                    ano para anual.</small
                >
            </div>
        </form>
        <template #footer
            ><Button
                label="Cancelar"
                severity="danger"
                text
                :disabled="saving"
                @click="visible = false" /><Button
                label="Salvar alterações"
                icon="pi pi-check"
                type="submit"
                form="subscriptionForm"
                :loading="saving"
        /></template>
    </Dialog>
</template>

<style scoped>
.subscription-summary {
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    background: var(--p-primary-50);
    border: 1px solid var(--p-primary-200);
}
.subscription-summary strong {
    color: var(--p-primary-color);
    font-size: 1.2rem;
}
.subscription-summary span {
    color: var(--p-text-muted-color);
}
</style>
