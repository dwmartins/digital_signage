<script setup>
import { showAlert } from "@/helpers/alert";
import mediaService from "@/services/media.service";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({ modelValue: Boolean, media: Object });
const emit = defineEmits(["update:modelValue", "saved"]);
const saving = ref(false);
const fieldErrors = reactive({});
const options = [
    { label: "Aprovada", value: "approved" },
    { label: "Rejeitada", value: "rejected" },
];
const form = reactive({ approval_status: "approved", rejection_reason: null });
const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const onSubmit = async () => {
    fieldErrors.rejection_reason =
        form.approval_status === "rejected" && !form.rejection_reason?.trim()
            ? "Informe o motivo da rejeição."
            : null;

    if (fieldErrors.rejection_reason) return;

    try {
        saving.value = true;
        const response = await mediaService.updateApproval(
            props.media.id,
            form,
        );
        showAlert("success", response.message);
        emit("saved", response.media);
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
        if (!opened) return;
        form.approval_status =
            props.media?.approval_status === "pending_approval"
                ? "approved"
                : props.media?.approval_status === "rejected"
                  ? "rejected"
                  : "approved";
        form.rejection_reason = props.media?.rejection_reason ?? null;
        fieldErrors.rejection_reason = null;
    },
);
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Analisar mídia"
        :style="{ width: '34rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
    >
        <form id="mediaApprovalForm" class="row g-3" @submit.prevent="onSubmit">
            <div class="col-12">
                <div class="field">
                    <label>Mídia</label>
                    <InputText :modelValue="media?.name" disabled fluid />
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label
                        ><span class="text-danger me-1">*</span>Decisão</label
                    >
                    <Select
                        v-model="form.approval_status"
                        :options="options"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div v-if="form.approval_status === 'rejected'" class="col-12">
                <div class="field">
                    <label
                        ><span class="text-danger me-1">*</span>Motivo da
                        rejeição</label
                    >
                    <Textarea
                        v-model="form.rejection_reason"
                        rows="4"
                        maxlength="5000"
                        :invalid="!!fieldErrors.rejection_reason"
                        autoResize
                        fluid
                    />
                    <small
                        v-if="fieldErrors.rejection_reason"
                        class="text-danger"
                        >{{ fieldErrors.rejection_reason }}</small
                    >
                </div>
            </div>
        </form>

        <template #footer>
            <Button
                label="Cancelar"
                severity="danger"
                class="p-button-text"
                :disabled="saving"
                @click="visible = false"
            />
            <Button
                label="Salvar decisão"
                icon="pi pi-check"
                :loading="saving"
                type="submit"
                form="mediaApprovalForm"
            />
        </template>
    </Dialog>
</template>
