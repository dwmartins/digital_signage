<script setup>
import { showAlert } from '@/helpers/alert';
import categoryService from '@/services/category.service';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({ modelValue: Boolean, category: Object });
const emit = defineEmits(['update:modelValue', 'saved']);
const saving = ref(false);
const lastGeneratedSlug = ref('');
const fieldErrors = reactive({});
const form = reactive({ id: null, name: '', slug: '', description: '', status: 'active' });
const statusOptions = [
    { label: 'Ativa', value: 'active' },
    { label: 'Inativa', value: 'inactive' },
];
const visible = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
});
const isUpdate = computed(() => !!props.category?.id);

const slugify = value => value
    ?.normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') ?? '';

const generateSlug = () => {
    if (form.slug && form.slug !== lastGeneratedSlug.value) return;

    form.slug = slugify(form.name);
    lastGeneratedSlug.value = form.slug;
};

const onSubmit = async () => {
    if (!form.name?.trim()) {
        fieldErrors.name = 'Informe o nome.';
        return;
    }

    try {
        saving.value = true;
        const response = isUpdate.value
            ? await categoryService.update(form)
            : await categoryService.create(form);
        showAlert('success', response.message);
        emit('saved', response.category);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, opened => {
    if (!opened) return;
    Object.assign(form, { id: null, name: '', slug: '', description: '', status: 'active' }, props.category ?? {});
    lastGeneratedSlug.value = form.slug === slugify(form.name) ? form.slug : '';
    Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
});
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '42rem' }" :breakpoints="{ '768px': '94vw' }" :draggable="false" :header="`${isUpdate ? 'Editar' : 'Adicionar'} categoria`">
        <form id="categoryForm" class="row g-4" @submit.prevent="onSubmit">
            <div class="col-12 d-flex justify-content-end">
                <Tag :severity="form.status === 'active' ? 'success' : 'secondary'" :value="form.status === 'active' ? 'Ativa' : 'Inativa'" />
            </div>
            <div class="col-md-7"><div class="field">
                <label><span class="text-danger me-1">*</span>Nome</label>
                <InputText v-model="form.name" :invalid="!!fieldErrors.name" fluid @input="fieldErrors.name = null" @blur="generateSlug" />
                <small v-if="fieldErrors.name" class="text-danger">{{ fieldErrors.name }}</small>
            </div></div>
            <div class="col-md-5"><div class="field">
                <label>Status</label>
                <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
            </div></div>
            <div class="col-12"><div class="field">
                <label>Slug</label>
                <InputText v-model="form.slug" placeholder="Gerado automaticamente pelo nome" fluid />
            </div></div>
            <div class="col-12"><div class="field">
                <label>Descrição</label>
                <Textarea v-model="form.description" rows="4" maxlength="2000" fluid autoResize />
            </div></div>
        </form>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="danger" class="p-button-text" :disabled="saving" @click="visible = false" />
            <Button :label="saving ? 'Aguarde...' : (isUpdate ? 'Atualizar' : 'Salvar')" icon="pi pi-check" :loading="saving" type="submit" form="categoryForm" />
        </template>
    </Dialog>
</template>
