<script setup>
import { showAlert } from '@/helpers/alert';
import localityService from '@/services/locality.service';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    modelValue: Boolean,
    type: String,
    locality: Object,
    states: Array,
    cities: Array,
});

const emit = defineEmits(['update:modelValue', 'saved']);

const saving = ref(false);
const errors = reactive({});
const form = reactive({
    id: null,
    name: '',
    code: '',
    state_id: null,
    city_id: null,
    status: 'active',
});

const statusOptions = [
    { label: 'Ativa', value: 'active' },
    { label: 'Inativa', value: 'inactive' },
];

const visible = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
});

const isUpdate = computed(() => !!props.locality?.id);
const typeLabel = computed(() => ({
    states: 'estado',
    cities: 'cidade',
    neighborhoods: 'bairro',
})[props.type] ?? 'localidade');

const availableCities = computed(() => props.cities.filter(
    city => !form.state_id || city.state_id === form.state_id,
));

const submit = async () => {
    errors.name = form.name.trim() ? null : 'Informe o nome.';
    errors.code = props.type === 'states' && form.code.trim().length !== 2
        ? 'Informe a UF com dois caracteres.'
        : null;
    errors.state_id = ['cities', 'neighborhoods'].includes(props.type) && !form.state_id
        ? 'Selecione o estado.'
        : null;
    errors.city_id = props.type === 'neighborhoods' && !form.city_id
        ? 'Selecione a cidade.'
        : null;

    if (Object.values(errors).some(Boolean)) return;

    try {
        saving.value = true;
        const response = isUpdate.value
            ? await localityService.update(props.type, form)
            : await localityService.create(props.type, form);
        showAlert('success', response.message);
        emit('saved', response.locality);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => form.state_id, (stateId, previousStateId) => {
    if (previousStateId && stateId !== previousStateId) {
        form.city_id = null;
    }
});

watch(() => props.modelValue, opened => {
    if (!opened) return;

    Object.assign(form, {
        id: props.locality?.id ?? null,
        name: props.locality?.name ?? '',
        code: props.locality?.code ?? '',
        state_id: props.locality?.state_id
            ?? props.locality?.city?.state_id
            ?? null,
        city_id: props.locality?.city_id ?? null,
        status: props.locality?.status ?? 'active',
    });
    Object.keys(errors).forEach(key => delete errors[key]);
});
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="`${isUpdate ? 'Editar' : 'Adicionar'} ${typeLabel}`"
        :style="{ width: '38rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
    >
        <form id="localityForm" class="row g-4" @submit.prevent="submit">
            <div v-if="['cities', 'neighborhoods'].includes(type)" class="col-12">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Estado</label>
                    <Select
                        v-model="form.state_id"
                        :options="states"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecione o estado"
                        :invalid="!!errors.state_id"
                        filter
                        fluid
                    >
                        <template #option="{ option }">
                            {{ option.name }} ({{ option.code }})
                        </template>
                    </Select>
                    <small v-if="errors.state_id" class="text-danger">{{ errors.state_id }}</small>
                </div>
            </div>

            <div v-if="type === 'neighborhoods'" class="col-12">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Cidade</label>
                    <Select
                        v-model="form.city_id"
                        :options="availableCities"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecione a cidade"
                        :disabled="!form.state_id"
                        :invalid="!!errors.city_id"
                        filter
                        fluid
                    />
                    <small v-if="errors.city_id" class="text-danger">{{ errors.city_id }}</small>
                </div>
            </div>

            <div :class="type === 'states' ? 'col-md-8' : 'col-12'">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Nome</label>
                    <InputText
                        v-model="form.name"
                        maxlength="255"
                        :invalid="!!errors.name"
                        fluid
                    />
                    <small v-if="errors.name" class="text-danger">{{ errors.name }}</small>
                </div>
            </div>

            <div v-if="type === 'states'" class="col-md-4">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>UF</label>
                    <InputText
                        v-model="form.code"
                        maxlength="2"
                        :invalid="!!errors.code"
                        fluid
                    />
                    <small v-if="errors.code" class="text-danger">{{ errors.code }}</small>
                </div>
            </div>

            <div class="col-12">
                <div class="field">
                    <label>Status</label>
                    <Select
                        v-model="form.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
        </form>

        <template #footer>
            <Button
                label="Cancelar"
                severity="danger"
                text
                :disabled="saving"
                @click="visible = false"
            />
            <Button
                :label="isUpdate ? 'Atualizar' : 'Salvar'"
                icon="pi pi-check"
                type="submit"
                form="localityForm"
                :loading="saving"
            />
        </template>
    </Dialog>
</template>
