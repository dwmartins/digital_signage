<script setup>
import { showAlert } from '@/helpers/alert';
import { validateForm } from '@/helpers/validations';
import establishmentService from '@/services/establishment.service';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({ modelValue: Boolean, establishment: Object });
const emit = defineEmits(['update:modelValue', 'saved']);
const saving = ref(false);
const fieldErrors = reactive({});
const statusOptions = [
    { label: 'Ativo', value: 'active' },
    { label: 'Inativo', value: 'inactive' },
    { label: 'Bloqueado', value: 'blocked' },
];
const stateOptions = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
const defaults = () => ({
    id: null,
    name: '',
    legal_name: '',
    document: '',
    phone: '',
    email: '',
    contact_name: '',
    address: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
    zip_code: '',
    latitude: null,
    longitude: null,
    status: 'active',
    opening_hours: '',
    notes: '',
});
const form = reactive(defaults());
const visible = computed({ get: () => props.modelValue, set: value => emit('update:modelValue', value) });
const isUpdate = computed(() => !!props.establishment?.id);
const clearError = field => fieldErrors[field] = null;
const clean = value => value?.replace(/[^a-zA-Z0-9]/g, '').toUpperCase() || null;

const payload = () => ({
    ...form,
    document: clean(form.document),
    phone: clean(form.phone),
    zip_code: clean(form.zip_code),
    latitude: form.latitude || null,
    longitude: form.longitude || null,
});

const onSubmit = async () => {
    const required = [
        { id: 'name', label: 'Nome' },
        { id: 'document', label: 'CNPJ' },
        { id: 'address', label: 'Endereço' },
        { id: 'city', label: 'Cidade' },
        { id: 'state', label: 'Estado' },
        { id: 'status', label: 'Status' },
    ];
    if (!validateForm(form, required, fieldErrors)) return;

    try {
        saving.value = true;
        const response = isUpdate.value
            ? await establishmentService.update(payload())
            : await establishmentService.create(payload());
        showAlert('success', response.message);
        emit('saved', response.establishment);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, opened => {
    if (!opened) return;
    Object.assign(form, defaults(), props.establishment ?? {});
    form.latitude = form.latitude === null ? null : Number(form.latitude);
    form.longitude = form.longitude === null ? null : Number(form.longitude);
    Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
});
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '64rem' }" :breakpoints="{ '992px': '94vw' }" :draggable="false" :header="`${isUpdate ? 'Editar' : 'Adicionar'} estabelecimento`">
        <form id="establishmentForm" class="row g-4" @submit.prevent="onSubmit">
            <div class="col-12 d-flex justify-content-end">
                <Tag :severity="form.status === 'active' ? 'success' : (form.status === 'blocked' ? 'danger' : 'secondary')" :value="statusOptions.find(item => item.value === form.status)?.label" />
            </div>

            <div class="col-12"><Divider align="left"><b>Identificação</b></Divider></div>
            <div class="col-md-6"><div class="field">
                <label><span class="text-danger me-1">*</span>Nome do estabelecimento</label>
                <InputText v-model="form.name" :invalid="!!fieldErrors.name" fluid @input="clearError('name')" />
            </div></div>
            <div class="col-md-6"><div class="field">
                <label>Razão social</label>
                <InputText v-model="form.legal_name" fluid />
            </div></div>
            <div class="col-md-4"><div class="field">
                <label><span class="text-danger me-1">*</span>CNPJ</label>
                <InputMask v-model="form.document" mask="**.***.***/****-99" :invalid="!!fieldErrors.document" fluid @change="clearError('document')" />
            </div></div>
            <div class="col-md-4"><div class="field">
                <label>Status</label>
                <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
            </div></div>

            <div class="col-12"><Divider align="left"><b>Contato</b></Divider></div>
            <div class="col-md-4"><div class="field"><label>Responsável</label><InputText v-model="form.contact_name" fluid /></div></div>
            <div class="col-md-4"><div class="field"><label>Telefone</label><InputMask v-model="form.phone" mask="(99) 99999-9999" fluid /></div></div>
            <div class="col-md-4"><div class="field"><label>E-mail</label><InputText v-model="form.email" type="email" fluid /></div></div>

            <div class="col-12"><Divider align="left"><b>Endereço</b></Divider></div>
            <div class="col-md-3"><div class="field"><label>CEP</label><InputMask v-model="form.zip_code" mask="99999-999" fluid /></div></div>
            <div class="col-md-7"><div class="field"><label><span class="text-danger me-1">*</span>Endereço</label><InputText v-model="form.address" :invalid="!!fieldErrors.address" fluid @input="clearError('address')" /></div></div>
            <div class="col-md-2"><div class="field"><label>Número</label><InputText v-model="form.number" fluid /></div></div>
            <div class="col-md-4"><div class="field"><label>Complemento</label><InputText v-model="form.complement" fluid /></div></div>
            <div class="col-md-3"><div class="field"><label>Bairro</label><InputText v-model="form.neighborhood" fluid /></div></div>
            <div class="col-md-3"><div class="field"><label><span class="text-danger me-1">*</span>Cidade</label><InputText v-model="form.city" :invalid="!!fieldErrors.city" fluid @input="clearError('city')" /></div></div>
            <div class="col-md-2"><div class="field"><label><span class="text-danger me-1">*</span>UF</label><Select v-model="form.state" :options="stateOptions" filter :invalid="!!fieldErrors.state" fluid @change="clearError('state')" /></div></div>

            <div class="col-12"><Divider align="left"><b>Localização e operação</b></Divider></div>
            <div class="col-md-3"><div class="field"><label>Latitude</label><InputNumber v-model="form.latitude" :minFractionDigits="0" :maxFractionDigits="7" fluid /></div></div>
            <div class="col-md-3"><div class="field"><label>Longitude</label><InputNumber v-model="form.longitude" :minFractionDigits="0" :maxFractionDigits="7" fluid /></div></div>
            <div class="col-md-6"><div class="field"><label>Horário de funcionamento</label><InputText v-model="form.opening_hours" placeholder="Ex.: Segunda a sexta, 08h às 18h" fluid /></div></div>
            <div class="col-12"><div class="field"><label>Observações</label><Textarea v-model="form.notes" rows="4" maxlength="5000" autoResize fluid /></div></div>
        </form>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" severity="danger" class="p-button-text" :disabled="saving" @click="visible = false" />
            <Button :label="saving ? 'Aguarde...' : (isUpdate ? 'Atualizar' : 'Salvar')" icon="pi pi-check" :loading="saving" type="submit" form="establishmentForm" />
        </template>
    </Dialog>
</template>
