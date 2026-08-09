<script setup>
import { showAlert } from '@/helpers/alert';
import { safeISOToDate } from '@/helpers/date';
import { capitalizeFirstLetter } from '@/helpers/functions';
import { validateForm } from '@/helpers/validations';
import profileService from '@/services/profile.service';
import { useAuthStore } from '@/stores/authStore';
import { computed, reactive, ref } from 'vue';

const authStore = useAuthStore();
const user      = authStore.user;

const form = reactive({
    name:           user.name,
    last_name:      user.last_name,
    email:          user.email,
    document:       user.document,
    professional_document: user.professional_document,
    phone:          user.phone ?? '',
    birth_date:     user.birth_date,
    description:    user.description
});

const saving      = ref(false);
const fieldErrors = reactive({});
const professionalDocumentRequirement = computed(() => user.professional_document_requirement || {
    required: false,
    types: [],
});

const toPayload = (data) => ({
    ...data,
    name: capitalizeFirstLetter(data.name),
    last_name: capitalizeFirstLetter(data.last_name),
    professional_document: data.professional_document
        ? data.professional_document.replace(/[^a-zA-Z0-9]/g, '')
        : null,
    phone: form.phone ? form.phone.replace(/[^\d+]/g, '').replace(/(?!^)\+/g, '') : null,
    birth_date: form.birth_date ? safeISOToDate(form.birth_date) : null
});

const onSubmit = async () => {
    const requiredFields = [
        {id: 'name',      label: 'Nome'},
        {id: 'last_name', label: 'Sobrenome'},
        {id: 'email',     label: 'E-mail'},
        {id: 'phone',     label: 'Telefone'},
    ]; 

    if (professionalDocumentRequirement.value.required) {
        requiredFields.push({
            id: 'professional_document',
            label: 'Documento profissional',
        });
    }

    if(!validateForm(form, requiredFields, fieldErrors)) return;

    try {
        saving.value = true;
        const payload = toPayload(form);

        const response = await profileService.update(payload);

        authStore.update(response.user);
        showAlert('success', response.message);
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
}

const countDescription = () => {
    if(form.description?.length > 500) {
        form.description = form.description.substring(0, 500);
    }
}
</script>

<template>
    <Card>
        <template #title>
            <i class="pi pi-user" />
            Informações Pessoais
        </template>
        <template #content>
            <form @submit.prevent="onSubmit" class="row g-3 mt-1">
                <div class="col-12 col-sm-4">
                    <div class="field">
                        <label>Nome <span class="text-danger">*</span></label>
                        <InputText
                            v-model="form.name"
                            class="w-100"
                            :invalid="!!fieldErrors.name" 
                            @input="fieldErrors.name = null" 
                            required
                        />
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="field">
                        <label>Sobrenome <span class="text-danger">*</span></label>
                        <InputText
                            v-model="form.last_name"
                            class="w-100"
                            :invalid="!!fieldErrors.last_name" 
                            @input="fieldErrors.last_name = null" 
                        />
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="field">
                        <label>Data de nascimento</label>
                        <DatePicker
                            v-model="form.birth_date"
                            dateFormat="dd/mm/yy"
                            showIcon
                            class="w-100"
                            :maxDate="new Date()"
                        />
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="field">
                        <label>E-mail <span class="text-danger">*</span></label>
                        <InputText
                            v-model="form.email"
                            class="w-100"
                            type="email"
                            :invalid="!!fieldErrors.email" 
                            @input="fieldErrors.email = null" 
                        />
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="field">
                        <label>Telefone <span class="text-danger">*</span></label>
                        <InputMask
                            v-model="form.phone"
                            mask="(99) 99999-9999"
                            placeholder="(00) 00000-0000"
                            class="w-100"
                            :invalid="!!fieldErrors.phone" 
                            @input="fieldErrors.phone = null" 
                        />
                    </div>
                </div>
                <div class="col-12">
                    <div class="field">
                        <label>Descrição</label>
                        <div class="position-relative">
                            <Textarea 
                                v-model="form.description" 
                                @input="countDescription" 
                                autoResize 
                                rows="5" 
                                cols="30" 
                                maxlength="500" 
                                class="w-100" 
                                id="description" 
                                placeholder="Descrição do usuário..."
                            />
                            <span class="text-secondary fs-7 counter-description">{{ form.description?.length ?? 0}} / 500</span>  
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <Button
                        label="Salvar Alterações"
                        icon="pi pi-check"
                        :loading="saving"
                        type="submit"
                        size="small"
                    />
                </div>
            </form>
        </template>
    </Card>
</template>
