<script setup>
import { showAlert } from '@/helpers/alert';
import { validateForm } from '@/helpers/validations';
import profileService from '@/services/profile.service';
import { reactive, ref } from 'vue';
import Card from 'primevue/card';
import Password from 'primevue/password';
import Divider from 'primevue/divider';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const fieldErrors = reactive({});
const saving = ref(false);

const form = reactive({
    new_password: '',
    new_password_confirmation: '',
    current_password: '',
    force_logout: false
});

const onSubmit = async () => {
    const requiredFields = [
        {id: 'new_password',              label: 'Nova senha'},
        {id: 'new_password_confirmation', label: 'Confirmar Senha'},
        {id: 'current_password',          label: 'Senha atual'},
    ];

    if(!validateForm(form, requiredFields, fieldErrors)) return;

    if (form.new_password !== form.new_password_confirmation) {
        showAlert('error', 'As senhas não coincidem.');
        return;
    }

    if(form.new_password && form.new_password.length < 8) {
        showAlert('error', 'A senha deve ter no mínimo 8 caracteres.');
        return;
    }

    try {
        saving.value = true;
        const response = await profileService.updatePassword(form);
        showAlert('success', response.message);

        Object.keys(form).forEach(key => form[key] = '');
        form.force_logout = false;
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
}

</script>

<template>
    <Card>
        <template #title>
            <div class="d-flex align-items-center gap-2">
                <i class="pi pi-lock" />
                Alterar Senha
            </div>
        </template>
        <template #content>
            <form @submit.prevent="onSubmit" class="row g-3 mt-1">
                <div class="col-12 col-sm-6">
                    <div class="field">
                        <label>Nova senha</label>
                        <Password 
                            v-model="form.new_password" 
                            toggleMask
                            placeholder="Digite a nova senha"
                            :invalid="!!fieldErrors.new_password" 
                            @input="fieldErrors.new_password = null" 
                            fluid
                        >
                            <template #footer>
                                <Divider/>
                                <ul>
                                    <li>
                                        <small>Mínimo de 8 caracteres</small>
                                    </li>
                                </ul>
                            </template>
                        </Password>
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="field">
                        <label>Confirmar Senha</label>
                        <Password 
                            v-model="form.new_password_confirmation"
                            toggleMask
                            :feedback="false"
                            placeholder="Confirme a nova senha"
                            :invalid="!!fieldErrors.new_password_confirmation" 
                            @input="fieldErrors.new_password_confirmation = null" 
                            fluid
                        />
                    </div>
                </div>
                 <div class="col-12 col-sm-6">
                    <div class="field">
                        <label>Senha atual</label>
                        <Password 
                            v-model="form.current_password"
                            toggleMask
                            :feedback="false"
                            placeholder="Digite sua senha atual"
                            :invalid="!!fieldErrors.current_password" 
                            @input="fieldErrors.current_password = null" 
                            fluid
                        />
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <Checkbox 
                        v-model="form.force_logout" 
                        inputId="logout_all" 
                        binary
                    />
                    <label for="logout_all"> Quero desconectar de todos os dispositivos. </label>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <Button
                        label="Atualizar senha"
                        icon="pi pi-lock"
                        severity="secondary"
                        :loading="saving"
                        type="submit"
                        size="small"
                    />
                </div>
            </form>
        </template>
    </Card>
</template>