<script setup>
import { showAlert } from '@/helpers/alert';
import { validateForm } from '@/helpers/validations';
import screenService from '@/services/screen.service';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({ modelValue: Boolean, screen: Object });
const emit = defineEmits(['update:modelValue', 'saved']);
const saving = ref(false);
const fieldErrors = reactive({});
const statusOptions = [
    { label: 'Ativa', value: 'active' },
    { label: 'Manutenção', value: 'maintenance' },
    { label: 'Bloqueada', value: 'blocked' },
    { label: 'Estoque', value: 'stock' },
];
const orientationOptions = [
    { label: 'Horizontal', value: 'landscape' },
    { label: 'Vertical', value: 'portrait' },
];
const resolutionOptions = [
    { label: 'HD — 1280 × 720', width: 1280, height: 720 },
    { label: 'Full HD — 1920 × 1080', width: 1920, height: 1080 },
    { label: '4K — 3840 × 2160', width: 3840, height: 2160 },
];
const defaults = () => ({
    id: null,
    name: '',
    code: '',
    brand: '',
    model: '',
    screen_size: null,
    orientation: 'landscape',
    resolution_width: 1920,
    resolution_height: 1080,
    status: 'active',
    notes: '',
});
const form = reactive(defaults());
const visible = computed({ get: () => props.modelValue, set: value => emit('update:modelValue', value) });
const isUpdate = computed(() => !!props.screen?.id);
const selectedResolution = computed({
    get: () => resolutionOptions.find(option => option.width === form.resolution_width && option.height === form.resolution_height) ?? null,
    set: option => {
        if (!option) return;
        form.resolution_width = option.width;
        form.resolution_height = option.height;
    },
});
const clearError = field => fieldErrors[field] = null;

const generateCode = () => {
    if (form.code || isUpdate.value) return;
    const suffix = Math.random().toString(36).slice(2, 8).toUpperCase();
    form.code = `TELA-${suffix}`;
};

const onSubmit = async () => {
    const required = [
        { id: 'name', label: 'Nome' },
        { id: 'code', label: 'Código' },
        { id: 'orientation', label: 'Orientação' },
        { id: 'resolution_width', label: 'Largura' },
        { id: 'resolution_height', label: 'Altura' },
        { id: 'status', label: 'Status' },
    ];
    if (!validateForm(form, required, fieldErrors)) return;

    try {
        saving.value = true;
        const response = isUpdate.value
            ? await screenService.update(form)
            : await screenService.create(form);
        showAlert('success', response.message);
        emit('saved', response.screen);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, opened => {
    if (!opened) return;
    Object.assign(form, defaults(), props.screen ?? {});
    form.screen_size = form.screen_size === null ? null : Number(form.screen_size);
    Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
    generateCode();
});
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '58rem' }" :breakpoints="{ '992px': '94vw' }" :draggable="false" :header="`${isUpdate ? 'Editar' : 'Adicionar'} tela`">
        <form id="screenForm" class="row g-4" @submit.prevent="onSubmit">
            <div class="col-12 d-flex justify-content-end">
                <Tag :severity="({ active: 'success', maintenance: 'warn', blocked: 'danger', stock: 'contrast' })[form.status]" :value="statusOptions.find(item => item.value === form.status)?.label" />
            </div>

            <div class="col-12"><Divider align="left"><b>Identificação</b></Divider></div>
            <div class="col-md-5"><div class="field">
                <label>Status</label>
                <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
            </div></div>
            <div class="col-md-7"><div class="field">
                <label><span class="text-danger me-1">*</span>Nome da tela</label>
                <InputText v-model="form.name" placeholder="Ex.: Tela da recepção" :invalid="!!fieldErrors.name" fluid @input="clearError('name')" />
            </div></div>
            <div class="col-md-5"><div class="field">
                <label><span class="text-danger me-1">*</span>Código único</label>
                <InputText v-model="form.code" maxlength="64" :invalid="!!fieldErrors.code" fluid @input="form.code = form.code.toUpperCase(); clearError('code')" />
            </div></div>

            <div class="col-12"><Divider align="left"><b>Equipamento</b></Divider></div>
            <div class="col-md-4"><div class="field">
                <label>Marca</label>
                <InputText v-model="form.brand" maxlength="255" placeholder="Ex.: Samsung" fluid />
            </div></div>
            <div class="col-md-5"><div class="field">
                <label>Modelo</label>
                <InputText v-model="form.model" maxlength="255" placeholder="Ex.: LH55QMBEBGCXZD" fluid />
            </div></div>
            <div class="col-md-3"><div class="field">
                <label>Tamanho (polegadas)</label>
                <InputNumber v-model="form.screen_size" suffix=" ″" :min="1" :max="999.9" :minFractionDigits="0" :maxFractionDigits="1" fluid />
            </div></div>

            <div class="col-12"><Divider align="left"><b>Configuração de exibição</b></Divider></div>
            <div class="col-md-4"><div class="field"><label>Orientação</label><Select v-model="form.orientation" :options="orientationOptions" optionLabel="label" optionValue="value" fluid /></div></div>
            <div class="col-md-4"><div class="field"><label>Resolução padrão</label><Select v-model="selectedResolution" :options="resolutionOptions" optionLabel="label" placeholder="Personalizada" showClear fluid /></div></div>
            <div class="col-6 col-md-2"><div class="field"><label>Largura</label><InputNumber v-model="form.resolution_width" :min="240" :max="16384" :useGrouping="false" fluid /></div></div>
            <div class="col-6 col-md-2"><div class="field"><label>Altura</label><InputNumber v-model="form.resolution_height" :min="240" :max="16384" :useGrouping="false" fluid /></div></div>
            <div class="col-12"><div class="field"><label>Observações</label><Textarea v-model="form.notes" rows="4" maxlength="5000" autoResize fluid /></div></div>
        </form>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="danger" class="p-button-text" :disabled="saving" @click="visible = false" />
            <Button :label="saving ? 'Aguarde...' : (isUpdate ? 'Atualizar' : 'Salvar')" icon="pi pi-check" :loading="saving" type="submit" form="screenForm" />
        </template>
    </Dialog>
</template>
