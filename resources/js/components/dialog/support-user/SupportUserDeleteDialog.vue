<script setup>
import { showAlert } from '@/helpers/alert';
import supportUserService from '@/services/support-user.service';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: Boolean,
    supportUser: Object,
});

const emit = defineEmits(['update:modelValue', 'deleted']);

const deleting = ref(false);

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const onDelete = async () => {
    if (!props.supportUser?.id) return;

    try {
        deleting.value = true;

        const response = await supportUserService.destroy(props.supportUser.id);

        showAlert('success', response.message);
        emit('deleted', props.supportUser);
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
        :style="{ width: '32rem' }"
        :breakpoints="{ '576px': '94vw' }"
        :draggable="false"
        header="Excluir usuário suporte"
    >
        <div class="d-flex justify-content-center align-items-center gap-3">
            <span>
                <i class="pi pi-exclamation-triangle text-danger fs-2"></i>
            </span>
            <div>
                <p class="mb-2">
                    Tem certeza que deseja excluir o usuário suporte
                    <strong>{{ supportUser?.full_name || supportUser?.name }}</strong>?
                </p>
                <p class="text-muted mb-0">
                    Esta ação não poderá ser desfeita.
                </p>
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancelar"
                icon="pi pi-times"
                class="p-button-text"
                severity="secondary"
                :disabled="deleting"
                @click="visible = false"
            />
            <Button
                :label="deleting ? 'Excluindo...' : 'Excluir'"
                icon="pi pi-trash"
                severity="danger"
                :loading="deleting"
                @click="onDelete"
            />
        </template>
    </Dialog>
</template>
