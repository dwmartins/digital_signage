<script setup>
import { showAlert } from '@/helpers/alert';
import screenService from '@/services/screen.service';
import { computed, ref } from 'vue';

const props = defineProps({ modelValue: Boolean, screen: Object });
const emit = defineEmits(['update:modelValue', 'deleted']);
const deleting = ref(false);
const visible = computed({ get: () => props.modelValue, set: value => emit('update:modelValue', value) });

const onDelete = async () => {
    if (!props.screen?.id) return;
    try {
        deleting.value = true;
        const response = await screenService.destroy(props.screen.id);
        showAlert('success', response.message);
        emit('deleted', props.screen);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '32rem' }" :breakpoints="{ '576px': '94vw' }" :draggable="false" header="Excluir tela">
        <div class="d-flex justify-content-center align-items-center gap-3">
            <i class="pi pi-exclamation-triangle text-danger fs-2"></i>
            <div>
                <p class="mb-2">Tem certeza que deseja excluir <strong>{{ screen?.name }}</strong>?</p>
                <p class="text-muted mb-0">Esta ação não poderá ser desfeita.</p>
            </div>
        </div>
        <template #footer>
            <Button label="Cancelar" severity="secondary" class="p-button-text" :disabled="deleting" @click="visible = false" />
            <Button :label="deleting ? 'Excluindo...' : 'Excluir'" icon="pi pi-trash" severity="danger" :loading="deleting" @click="onDelete" />
        </template>
    </Dialog>
</template>
