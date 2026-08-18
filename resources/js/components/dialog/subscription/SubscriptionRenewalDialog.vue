<script setup>
import { showAlert } from "@/helpers/alert";
import { formatDate } from "@/helpers/date";
import subscriptionService from "@/services/subscription.service";
import { computed, ref, watch } from "vue";

const props = defineProps({
    modelValue: Boolean,
    subscription: Object,
});

const emit = defineEmits(["update:modelValue", "renewed"]);
const renewing = ref(false);
const paymentMethod = ref(null);
const paymentDate = ref(new Date());

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

const isFree = computed(() => Number(props.subscription?.price ?? 0) === 0);
const cycleLabel = computed(() =>
    props.subscription?.billing_cycle === "yearly" ? "Anual" : "Mensal",
);

const money = (value) =>
    new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value ?? 0);

const formatPaymentDate = (date) => {
    if (!date) return null;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
};

const newEndsAt = computed(() => {
    const currentEnd = props.subscription?.ends_at
        ? new Date(props.subscription.ends_at)
        : null;
    const reference = currentEnd && currentEnd > new Date()
        ? new Date(currentEnd)
        : new Date();

    if (props.subscription?.billing_cycle === "yearly") {
        reference.setFullYear(reference.getFullYear() + 1);
    } else {
        reference.setDate(reference.getDate() + 30);
    }

    return reference;
});

const renew = async () => {
    if (!isFree.value && !paymentMethod.value) {
        return showAlert("warning", "Selecione o método de pagamento utilizado.");
    }

    if (!isFree.value && !paymentDate.value) {
        return showAlert("warning", "Informe a data em que o pagamento foi realizado.");
    }

    try {
        renewing.value = true;
        const response = await subscriptionService.renew(
            props.subscription.id,
            {
                payment_method: paymentMethod.value,
                payment_date: formatPaymentDate(paymentDate.value),
                expected_ends_at: props.subscription.ends_at,
            },
        );
        showAlert("success", response.message);
        emit("renewed");
        visible.value = false;
    } catch (error) {
        showAlert("error", error.response?.data);
    } finally {
        renewing.value = false;
    }
};

watch(
    () => props.modelValue,
    (opened) => {
        if (opened) {
            paymentMethod.value = null;
            paymentDate.value = new Date();
        }
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Renovar assinatura"
        :style="{ width: '36rem' }"
        :breakpoints="{ '576px': '94vw' }"
        :draggable="false"
    >
        <div class="d-flex align-items-start gap-3">
            <span class="renewal-icon flex-shrink-0">
                <i class="pi pi-refresh"></i>
            </span>

            <div class="flex-grow-1 min-w-0">
                <p class="mb-3">
                    Confirme a renovação da assinatura
                    <strong>#{{ subscription?.id }}</strong>
                    <template v-if="subscription?.campaign?.name">
                        da campanha <strong>{{ subscription.campaign.name }}</strong>
                    </template>.
                </p>

                <div class="renewal-summary rounded-3 mb-4">
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Plano</span>
                        <strong class="text-end">{{ subscription?.plan?.name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Ciclo</span>
                        <strong>{{ cycleLabel }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Valor</span>
                        <strong>{{ isFree ? "Grátis" : money(subscription?.price) }}</strong>
                    </div>
                    <div class="renewal-period pt-3 mt-2">
                        <div>
                            <small class="d-block text-muted">Término atual</small>
                            <strong>{{ formatDate(subscription?.ends_at) }}</strong>
                        </div>
                        <i class="pi pi-arrow-right text-primary"></i>
                        <div class="text-end">
                            <small class="d-block text-muted">Novo término</small>
                            <strong class="text-primary">{{ formatDate(newEndsAt) }}</strong>
                        </div>
                    </div>
                </div>

                <div v-if="!isFree" class="field mb-3">
                    <label><span class="text-danger me-1">*</span>Método de pagamento</label>
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

                <div v-if="!isFree" class="field mb-3">
                    <label for="renewal-payment-date">
                        <span class="text-danger me-1">*</span>Data do pagamento
                    </label>
                    <DatePicker
                        v-model="paymentDate"
                        inputId="renewal-payment-date"
                        dateFormat="dd/mm/yy"
                        :maxDate="new Date()"
                        showIcon
                        fluid
                    />
                </div>

                <small class="text-muted">
                    {{
                        isFree
                            ? "A vigência será renovada sem gerar fatura ou transação."
                            : "Uma nova fatura e uma nova transação paga serão criadas para esta renovação."
                    }}
                </small>
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancelar"
                severity="secondary"
                text
                :disabled="renewing"
                @click="visible = false"
            />
            <Button
                label="Confirmar renovação"
                icon="pi pi-refresh"
                severity="success"
                :loading="renewing"
                :disabled="!isFree && (!paymentMethod || !paymentDate)"
                @click="renew"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.min-w-0 {
    min-width: 0;
}

.renewal-icon {
    display: grid;
    place-items: center;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    color: var(--p-primary-color);
    background: var(--p-primary-50);
    font-size: 1.25rem;
}

.renewal-summary {
    display: grid;
    gap: 0.65rem;
    padding: 1rem;
    background: var(--p-surface-100);
}

.renewal-period {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 1rem;
    border-top: 1px solid var(--p-content-border-color);
}
</style>
