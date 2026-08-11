<script setup>
import { showAlert } from '@/helpers/alert';
import { capitalizeFirstLetter } from '@/helpers/functions';
import { validateForm } from '@/helpers/validations';
import supportUserService from '@/services/support-user.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    modelValue: Boolean,
    supportUser: Object,
});

const authStore = useAuthStore();

const emit = defineEmits(['update:modelValue', 'saved']);

const saving = ref(false);
const fieldErrors = reactive({});

const statusOptions = [
    { name: 'Ativo', code: 'active' },
    { name: 'Inativo', code: 'inactive' },
    { name: 'Bloqueado', code: 'blocked' },
];

const auditOptions = [
    { name: 'Ativada', code: true },
    { name: 'Desativada', code: false },
];

const getDefaultSupportUser = () => ({
    id: null,
    name: null,
    last_name: null,
    email: null,
    phone: null,
    status: 'active',
    password: null,
    audit_logs_enabled: true
});

const form = reactive(getDefaultSupportUser());

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const isUpdate = computed(() => !!props.supportUser?.id);

const canManageAudit = computed(() => authStore.hasPermission('companies.audit.update'));

const resetForm = () => {
    Object.assign(form, getDefaultSupportUser());
    Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
};

const fillForm = (data) => {
    if (!data) return;

    Object.assign(form, {
        ...getDefaultSupportUser(),
        ...data,
        password: null,
    });
};

const clearFieldError = (field) => {
    fieldErrors[field] = null;
};

const onlyLettersAndNumbers = (value) => {
    if (!value) return null;
    return value.replace(/[^a-zA-Z0-9]/g, '') || null;
};

const toPayload = () => ({
    id: form.id,
    name: capitalizeFirstLetter(form.name),
    last_name: capitalizeFirstLetter(form.last_name),
    email: form.email,
    phone: onlyLettersAndNumbers(form.phone),
    status: form.status,
    password: form.password || null,
    audit_logs_enabled: form.audit_logs_enabled
});

const onSubmit = async () => {
    const requiredFields = [
        { id: 'name', label: 'Nome' },
        { id: 'last_name', label: 'Sobrenome'},
        { id: 'email', label: 'E-mail' },
        { id: 'status', label: 'Status' },
        { id: 'phone', label: 'Telefone'}
    ];

    if (!isUpdate.value) {
        requiredFields.push({ id: 'password', label: 'Senha' });
    }

    if (!validateForm(form, requiredFields, fieldErrors)) return;

    try {
        saving.value = true;

        const response = isUpdate.value
            ? await supportUserService.update(toPayload())
            : await supportUserService.create(toPayload());

        showAlert('success', response.message);
        emit('saved', response.user);
        visible.value = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, (opened) => {
    if (!opened) return;

    resetForm();
    fillForm(props.supportUser);
});

</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :style="{ width: '48rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
        :header="`${isUpdate ? 'Editar' : 'Adicionar'} usuário suporte`"
    >
        <form id="supportUserForm" class="row g-4" @submit.prevent="onSubmit">
            <div class="col-12 d-flex justify-content-end">
                <div class="d-flex align-items-center gap-2">
                    <Tag
                        :severity="form.status === 'active' ? 'success' : (form.status === 'blocked' ? 'danger' : 'secondary')"
                        :value="statusOptions.find(item => item.code === form.status)?.name ?? 'Status'"
                    />
                </div>
            </div>

            <div class="col-md-6">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Nome</label>
                    <InputText
                        v-model="form.name"
                        :invalid="!!fieldErrors.name"
                        fluid
                        @input="clearFieldError('name')"
                    />
                </div>
            </div>

            <div class="col-md-6">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Sobrenome</label>
                    <InputText
                        v-model="form.last_name"
                        :invalid="!!fieldErrors.last_name"
                        fluid
                        @input="clearFieldError('last_name')"
                    />
                </div>
            </div>

            <div class="col-md-7">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>E-mail</label>
                    <InputText
                        v-model="form.email"
                        type="email"
                        :invalid="!!fieldErrors.email"
                        fluid
                        @input="clearFieldError('email')"
                    />
                </div>
            </div>

            <div class="col-md-5">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Status</label>
                    <Select
                        v-model="form.status"
                        :options="statusOptions"
                        optionLabel="name"
                        optionValue="code"
                        :invalid="!!fieldErrors.status"
                        fluid
                        @change="clearFieldError('status')"
                    />
                </div>
            </div>

            <div class="col-12">
                <Divider align="left" type="solid">
                    <b>Contato</b>
                </Divider>
            </div>

            <div class="col-md-6">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Telefone</label>
                    <InputMask
                        v-model="form.phone"
                        :invalid="!!fieldErrors.phone"
                        mask="(99) 99999-9999"
                        fluid
                        @change="clearFieldError('status')"
                    />
                </div>
            </div>

            <div class="col-12">
                <Divider align="left" type="solid">
                    <b>Acesso</b>
                </Divider>
            </div>

            <div class="col-md-7">
                <div class="field">
                    <label>
                        <span v-if="!isUpdate" class="text-danger me-1">*</span>Senha
                    </label>
                    <Password
                        v-model="form.password"
                        :feedback="false"
                        toggleMask
                        :placeholder="isUpdate ? 'Preencha apenas para alterar' : null"
                        :invalid="!!fieldErrors.password"
                        fluid
                        @input="clearFieldError('password')"
                    />
                </div>
            </div>

            <div class="col-12">
                <Divider align="left" type="solid">
                    <b>Configurações</b>
                </Divider>
            </div>

            <div class="col-md-5">
                <div class="field">
                    <label for="audit_logs_enabled">Auditoria</label>
                    <Select
                        id="audit_logs_enabled"
                        v-model="form.audit_logs_enabled"
                        :options="auditOptions"
                        optionLabel="name"
                        optionValue="code"
                        :disabled="!canManageAudit"
                        fluid
                    />
                    <small v-if="!canManageAudit" class="text-muted">
                        Você não possui permissão para alterar esta configuração.
                    </small>
                </div>
            </div>
        </form>

        <template #footer>
            <Button
                label="Cancelar"
                icon="pi pi-times"
                severity="danger"
                class="p-button-text"
                :disabled="saving"
                @click="visible = false"
            />
            <Button
                :label="saving ? 'Aguarde...' : (isUpdate ? 'Atualizar' : 'Salvar')"
                icon="pi pi-check"
                :loading="saving"
                type="submit"
                form="supportUserForm"
            />
        </template>
    </Dialog>
</template>