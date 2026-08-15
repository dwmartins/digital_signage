<script setup>
import { showAlert } from '@/helpers/alert';
import localityService from '@/services/locality.service';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: Boolean,
    type: String,
    locality: Object,
});

const emit = defineEmits(['update:modelValue', 'deleted']);
const deleting = ref(false);

const visible = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
});

const remove = async () => {
    try {
        deleting.value = true;
        const response = await localityService.destroy(props.type, props.locality.id);
        showAlert('success', response.message);
        emit('deleted');
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Excluir localidade"
        :style="{ width: '32rem' }"
        :breakpoints="{ '576px': '94vw' }"
        :draggable="false"
    >
        <div class="d-flex align-items-center gap-3">
            <i class="pi pi-exclamation-triangle text-danger fs-2"></i>
            <p class="mb-0">
                Deseja excluir <strong>{{ locality?.name }}</strong>?
                Localidades vinculadas não podem ser excluídas.
            </p>
        </div>

        <template #footer>
            <Button
                label="Cancelar"
                severity="secondary"
                text
                :disabled="deleting"
                @click="visible = false"
            />
            <Button
                label="Excluir"
                icon="pi pi-trash"
                severity="danger"
                :loading="deleting"
                @click="remove"
            />
        </template>
    </Dialog>
</template>
