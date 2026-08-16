<script setup>
import { showAlert } from '@/helpers/alert';
import service from '@/services/display-point.service';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({ modelValue: Boolean, item: Object });
const emit = defineEmits(['update:modelValue', 'saved']);
const saving = ref(false);
const options = reactive({ establishments: [], screens: [], players: [] });

const statuses = [
    { label: 'Ativo', value: 'active' },
    { label: 'Manutenção', value: 'maintenance' },
    { label: 'Inativo', value: 'inactive' },
];

const orientations = [
    { label: 'Horizontal', value: 'landscape', icon: 'pi pi-arrows-h' },
    { label: 'Vertical', value: 'portrait', icon: 'pi pi-arrows-v' },
];

const defaults = () => ({
    id: null,
    establishment_id: null,
    screen_id: null,
    player_id: null,
    name: '',
    location: '',
    orientation: 'landscape',
    status: 'active',
    notes: '',
});

const form = reactive(defaults());
const visible = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
});

const load = async () => Object.assign(options, await service.options(form.id));

const submit = async () => {
    if (!form.name || !form.establishment_id || !form.orientation) {
        return showAlert('warning', 'Informe o nome, o estabelecimento e a orientação.');
    }

    try {
        saving.value = true;
        const response = form.id
            ? await service.update(form)
            : await service.create(form);
        showAlert('success', response.message);
        emit('saved');
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, async opened => {
    if (!opened) return;
    Object.assign(form, defaults(), props.item ?? {});
    await load();
});
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :style="{ width: '54rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :header="`${form.id ? 'Editar' : 'Adicionar'} ponto de exibição`"
    >
        <form id="pointForm" class="row g-4" @submit.prevent="submit">
            <div class="col-md-8">
                <div class="field">
                    <label>Nome *</label>
                    <InputText v-model="form.name" fluid />
                </div>
            </div>
            <div class="col-md-4">
                <div class="field">
                    <label>Status</label>
                    <Select
                        v-model="form.status"
                        :options="statuses"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label>Estabelecimento *</label>
                    <Select
                        v-model="form.establishment_id"
                        :options="options.establishments"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        fluid
                    >
                        <template #option="{ option }">
                            <div class="d-flex flex-column">
                                <span>#{{ option.id }} - {{ option.name }}</span>
                                <small>{{ option.city?.name }} / {{ option.city?.state?.code }}</small>
                            </div>
                        </template>
                    </Select>
                </div>
            </div>
            <div class="col-md-7">
                <div class="field">
                    <label>Local de instalação</label>
                    <InputText v-model="form.location" fluid />
                </div>
            </div>
            <div class="col-md-5">
                <div class="field">
                    <label>Orientação da exibição *</label>
                    <Select
                        v-model="form.orientation"
                        :options="orientations"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    >
                        <template #option="{ option }">
                            <div class="d-flex align-items-center gap-2">
                                <i :class="option.icon"></i>
                                <span>{{ option.label }}</span>
                            </div>
                        </template>
                    </Select>
                    <small class="text-muted">
                        Define o formato dos conteúdos exibidos neste ponto.
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label>Tela</label>
                    <Select
                        v-model="form.screen_id"
                        :options="options.screens"
                        optionLabel="name"
                        optionValue="id"
                        showClear
                        filter
                        placeholder="Sem tela"
                        fluid
                    >
                        <template #option="{ option }">
                            #{{ option.id }} - {{ option.name }} ({{ option.code }})
                        </template>
                    </Select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="field">
                    <label>Player (PC)</label>
                    <Select
                        v-model="form.player_id"
                        :options="options.players"
                        optionLabel="name"
                        optionValue="id"
                        showClear
                        filter
                        placeholder="Sem player (PC)"
                        fluid
                    >
                        <template #option="{ option }">
                            #{{ option.id }} - {{ option.name }} ({{ option.code }})
                        </template>
                    </Select>
                </div>
            </div>
            <div class="col-12">
                <div class="field">
                    <label>Observações</label>
                    <Textarea v-model="form.notes" rows="4" fluid />
                </div>
            </div>
        </form>

        <template #footer>
            <Button label="Cancelar" severity="danger" text @click="visible = false" />
            <Button label="Salvar" :loading="saving" type="submit" form="pointForm" />
        </template>
    </Dialog>
</template>
