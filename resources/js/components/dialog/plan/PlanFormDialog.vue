<script setup>
import { showAlert } from "@/helpers/alert";
import planService from "@/services/plan.service";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({ modelValue: Boolean, plan: Object });
const emit = defineEmits(["update:modelValue", "saved"]);
const saving = ref(false);
const defaults = () => ({
    id: null,
    name: "",
    description: "",
    screen_limit: 1,
    billing_cycle: "monthly",
    media_type: "image",
    price: null,
    status: "active",
});
const form = reactive(defaults());
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
const isUpdate = computed(() => !!props.plan?.id);
const cycleOptions = [
    { label: "Mensal", value: "monthly" },
    { label: "Anual", value: "yearly" },
];
const mediaTypeOptions = [
    { label: "Imagem", value: "image" },
    { label: "Vídeo", value: "video" },
];
const statusOptions = [
    { label: "Ativo", value: "active" },
    { label: "Inativo", value: "inactive" },
];

const submit = async () => {
    try {
        saving.value = true;
        const response = isUpdate.value
            ? await planService.update(form)
            : await planService.create(form);
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
    () => props.modelValue,
    (opened) => {
        if (opened)
            Object.assign(form, defaults(), props.plan ?? {}, {
                price: props.plan?.price ? Number(props.plan.price) : null,
            });
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="`${isUpdate ? 'Editar' : 'Novo'} plano`"
        :style="{ width: '46rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
    >
        <form id="planForm" class="row g-4" @submit.prevent="submit">
            <div class="col-md-8">
                <div class="field">
                    <label>Nome</label
                    ><InputText v-model="form.name" required fluid />
                </div>
            </div>
            <div class="col-md-4">
                <div class="field">
                    <label>Status</label
                    ><Select
                        v-model="form.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-4">
                <div class="field">
                    <label>Quantidade de telas</label
                    ><InputNumber
                        v-model="form.screen_limit"
                        :min="1"
                        :max="10000"
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-4">
                <div class="field">
                    <label>Cobrança</label
                    ><Select
                        v-model="form.billing_cycle"
                        :options="cycleOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-4">
                <div class="field">
                    <label>Tipo de mídia</label
                    ><Select
                        v-model="form.media_type"
                        :options="mediaTypeOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div class="col-md-5">
                <div class="field">
                    <label>Valor</label
                    ><InputNumber
                        v-model="form.price"
                        mode="currency"
                        currency="BRL"
                        locale="pt-BR"
                        :min="0.01"
                        fluid
                    />
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label>Descrição</label
                    ><Textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="2000"
                        autoResize
                        fluid
                    />
                </div>
            </div>
        </form>
        <template #footer
            ><Button
                label="Cancelar"
                severity="danger"
                text
                @click="visible = false" /><Button
                label="Salvar plano"
                icon="pi pi-check"
                type="submit"
                form="planForm"
                :loading="saving"
        /></template>
    </Dialog>
</template>
