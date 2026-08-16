<script setup>
import { showAlert } from "@/helpers/alert";
import subscriptionService from "@/services/subscription.service";
import { computed, ref, watch } from "vue";

const props = defineProps({ modelValue: Boolean, subscription: Object });
const emit = defineEmits(["update:modelValue", "approved"]);
const approving = ref(false);
const paymentMethod = ref(null);
const paymentMethodOptions = [
    { label: "PIX", value: "pix", icon: "pi pi-qrcode" },
    { label: "Cartão de crédito", value: "credit_card", icon: "pi pi-credit-card" },
    { label: "Cartão de débito", value: "debit_card", icon: "pi pi-credit-card" },
    { label: "Boleto bancário", value: "bank_slip", icon: "pi pi-barcode" },
    { label: "Transferência bancária", value: "bank_transfer", icon: "pi pi-building-columns" },
    { label: "Dinheiro", value: "cash", icon: "pi pi-money-bill" },
];
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
const money = (value) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value ?? 0);
const isFree = computed(() => Number(props.subscription?.price ?? 0) === 0);
const subscriptionTarget = computed(() => props.subscription?.campaign?.name
    ? `da campanha ${props.subscription.campaign.name}`
    : `#${props.subscription?.id} de ${props.subscription?.customer?.name ?? "cliente anunciante"}`);

const approve = async () => {
    if (!isFree.value && !paymentMethod.value) {
        return showAlert("warning", "Selecione o método de pagamento utilizado.");
    }

    try {
        approving.value = true;
        const response = await subscriptionService.approve(
            props.subscription.id,
            { payment_method: paymentMethod.value },
        );
        showAlert("success", response.message);
        emit("approved");
        visible.value = false;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        approving.value = false;
    }
};

watch(() => props.modelValue, (opened) => {
    if (opened) paymentMethod.value = null;
});
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Aprovar assinatura"
        :style="{ width: '34rem' }"
        :breakpoints="{ '576px': '94vw' }"
        :draggable="false"
    >
        <div class="d-flex align-items-start gap-3">
            <div class="approval-icon"><i class="pi pi-check-circle"></i></div>
            <div>
                <p>
                    Deseja aprovar a assinatura
                    <strong>{{ subscriptionTarget }}</strong>?
                </p>
                <div class="approval-summary">
                    <span>{{ subscription?.plan?.name }}</span
                    ><strong>{{
                        isFree ? "Grátis" : money(subscription?.price)
                    }}</strong>
                </div>
                <div v-if="!isFree" class="field mb-3">
                    <label>Método de pagamento *</label>
                    <Select
                        v-model="paymentMethod"
                        :options="paymentMethodOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Selecione como o pagamento foi realizado"
                        fluid
                    >
                        <template #option="{ option }">
                            <div class="d-flex align-items-center gap-2">
                                <i :class="option.icon"></i>
                                <span>{{ option.label }}</span>
                            </div>
                        </template>
                    </Select>
                </div>
                <small class="text-muted">{{
                    isFree
                        ? "Esta ação ativará a assinatura sem gerar fatura ou transação."
                        : "Esta ação ativará somente a assinatura e criará uma fatura e uma transação já pagas. A campanha continuará com seu próprio status."
                }}</small>
            </div>
        </div>
        <template #footer
            ><Button
                label="Não, cancelar"
                severity="secondary"
                text
                :disabled="approving"
                @click="visible = false" /><Button
                label="Sim, aprovar"
                icon="pi pi-check"
                severity="success"
                :loading="approving"
                :disabled="!isFree && !paymentMethod"
                @click="approve"
        /></template>
    </Dialog>
</template>

<style scoped>
.approval-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 50%;
    color: var(--p-green-600);
    background: var(--p-green-50);
    font-size: 1.5rem;
}
.approval-summary {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin: 0.75rem 0;
    padding: 0.75rem 1rem;
    border-radius: 9px;
    background: var(--p-surface-100);
}
</style>
