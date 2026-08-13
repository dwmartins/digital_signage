<script setup>
import { showAlert } from '@/helpers/alert';
import mediaService from '@/services/media.service';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({ modelValue: Boolean, media: Object, customers: Array });
const emit = defineEmits(['update:modelValue', 'saved']);
const saving = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const fieldErrors = reactive({});
const defaults = () => ({
    id: null,
    user_id: null,
    name: '',
    description: '',
});
const form = reactive(defaults());
const visible = computed({ get: () => props.modelValue, set: value => emit('update:modelValue', value) });
const isUpdate = computed(() => !!props.media?.id);
const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm', 'video/quicktime'];

const readVideoDuration = file => new Promise((resolve, reject) => {
    const video = document.createElement('video');
    const objectUrl = URL.createObjectURL(file);

    video.preload = 'metadata';
    video.onloadedmetadata = () => {
        const duration = video.duration;
        URL.revokeObjectURL(objectUrl);
        resolve(duration);
    };
    video.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        reject(new Error('Não foi possível identificar a duração do vídeo.'));
    };
    video.src = objectUrl;
});

const onFileChange = async event => {
    const file = event.target.files?.[0] ?? null;
    fieldErrors.file = null;

    if (!file) {
        selectedFile.value = null;
        return;
    }

    if (!acceptedTypes.includes(file.type)) {
        fieldErrors.file = 'Selecione uma imagem JPG, PNG ou WEBP, ou um vídeo MP4, WEBM ou MOV.';
        event.target.value = '';
        return;
    }

    if (file.size > 100 * 1024 * 1024) {
        fieldErrors.file = 'O arquivo deve possuir no máximo 100 MB.';
        event.target.value = '';
        return;
    }

    if (file.type.startsWith('video/')) {
        try {
            const duration = await readVideoDuration(file);

            if (!Number.isFinite(duration) || duration <= 0) {
                throw new Error('Não foi possível identificar a duração do vídeo.');
            }

            if (duration > 15) {
                throw new Error('O vídeo deve possuir no máximo 15 segundos.');
            }
        } catch (error) {
            selectedFile.value = null;
            fieldErrors.file = error.message;
            event.target.value = '';
            showAlert('warning', error.message);
            return;
        }
    }

    selectedFile.value = file;

    if (!form.name) {
        form.name = file.name.replace(/\.[^.]+$/, '');
    }
};

const validate = () => {
    fieldErrors.user_id = form.user_id ? null : 'Selecione o anunciante.';
    fieldErrors.name = form.name?.trim() ? null : 'Informe o nome.';
    fieldErrors.file = selectedFile.value || isUpdate.value ? null : 'Selecione o arquivo da mídia.';

    return !Object.values(fieldErrors).some(Boolean);
};

const onSubmit = async () => {
    if (!validate()) return;

    try {
        saving.value = true;
        const payload = { ...form, file: selectedFile.value };
        const response = isUpdate.value
            ? await mediaService.update(payload)
            : await mediaService.create(payload);
        showAlert('success', response.message);
        emit('saved', response.media);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, opened => {
    if (!opened) return;
    Object.assign(form, defaults(), props.media ?? {});
    form.user_id = form.user_id ? Number(form.user_id) : null;
    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
    Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
});
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '52rem' }" :breakpoints="{ '768px': '94vw' }" :draggable="false" :header="`${isUpdate ? 'Editar' : 'Adicionar'} mídia`">
        <form id="mediaForm" class="row g-4" @submit.prevent="onSubmit">
            <div class="col-12"><Divider align="left"><b>Arquivo e anunciante</b></Divider></div>
            <div class="col-md-7"><div class="field">
                <label><span class="text-danger me-1">*</span>Cliente anunciante</label>
                <Select v-model="form.user_id" :options="customers" optionLabel="full_name" optionValue="id" filter :invalid="!!fieldErrors.user_id" fluid>
                    <template #option="{ option }">
                        <div class="d-flex flex-column"><span>#{{ option.id }} - {{ option.full_name }}</span><small class="text-muted">{{ option.email }}</small></div>
                    </template>
                </Select>
                <small v-if="fieldErrors.user_id" class="text-danger">{{ fieldErrors.user_id }}</small>
            </div></div>
            <div class="col-md-5"><div class="field">
                <label><span v-if="!isUpdate" class="text-danger me-1">*</span>{{ isUpdate ? 'Substituir arquivo' : 'Arquivo' }}</label>
                <input ref="fileInput" class="form-control" type="file" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov" @change="onFileChange" />
                <small v-if="fieldErrors.file" class="text-danger">{{ fieldErrors.file }}</small>
                <small v-else class="text-muted">Imagens ou vídeos de até 100 MB. Vídeos devem ter no máximo 15 segundos.</small>
            </div></div>

            <div class="col-12"><Divider align="left"><b>Identificação</b></Divider></div>
            <div class="col-12"><div class="field">
                <label><span class="text-danger me-1">*</span>Nome</label>
                <InputText v-model="form.name" maxlength="255" :invalid="!!fieldErrors.name" fluid />
                <small v-if="fieldErrors.name" class="text-danger">{{ fieldErrors.name }}</small>
            </div></div>
            <div class="col-12"><div class="field"><label>Descrição</label><Textarea v-model="form.description" rows="4" maxlength="5000" autoResize fluid /></div></div>
            <div v-if="isUpdate && selectedFile" class="col-12">
                <Message severity="warn" :closable="false">A substituição do arquivo enviará a mídia novamente para aprovação.</Message>
            </div>
        </form>

        <template #footer>
            <Button label="Cancelar" severity="danger" class="p-button-text" :disabled="saving" @click="visible = false" />
            <Button :label="saving ? 'Enviando...' : 'Salvar'" icon="pi pi-check" :loading="saving" type="submit" form="mediaForm" />
        </template>
    </Dialog>
</template>
