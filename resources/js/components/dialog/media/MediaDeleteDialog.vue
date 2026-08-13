<script setup>
import { showAlert } from '@/helpers/alert';
import mediaService from '@/services/media.service';
import { computed, ref } from 'vue';

const props = defineProps({ modelValue: Boolean, media: Object });
const emit = defineEmits(['update:modelValue', 'deleted']);
const deleting = ref(false);
const visible = computed({ get: () => props.modelValue, set: value => emit('update:modelValue', value) });

const onDelete = async () => {
    try {
        deleting.value = true;
        const response = await mediaService.destroy(props.media.id);
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
    <Dialog v-model:visible="visible" modal :style="{ width: '32rem' }" :breakpoints="{ '576px': '94vw' }" :draggable="false" header="Excluir mídia">
        <p>Deseja excluir <strong>{{ media?.name }}</strong> e remover seu arquivo permanentemente?</p>
        <template #footer>
            <Button label="Cancelar" severity="secondary" text :disabled="deleting" @click="visible = false" />
            <Button label="Excluir" icon="pi pi-trash" severity="danger" :loading="deleting" @click="onDelete" />
        </template>
    </Dialog>
</template>
