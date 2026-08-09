<script setup>
import { showAlert } from '@/helpers/alert';
import { logo } from '@/helpers/constants';
import { validateForm } from '@/helpers/validations';
import authService from '@/services/auth.service';
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route  = useRoute();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
    remember_me: false
});

const loading     = ref(false);
const fieldErrors = reactive({});

onMounted(() => {
    const emailFromQuery = route.query.email;
    const savedEmail = localStorage.getItem('last_email');

    if(emailFromQuery) {
        form.email = emailFromQuery;
    } else if(savedEmail) {
        form.email = savedEmail;
    }
});

const onSubmit = async () => {
    const required_fields = [
        {id: 'email', label: 'E-mail'},
        {id: 'password', label: 'Senha'}
    ];

    if(!validateForm(form, required_fields, fieldErrors)) return;

    try {
        loading.value = true;
        await authService.login(form);

        localStorage.setItem('last_email', form.email);

        const redirect = router.currentRoute.value.query.redirect;
        if(typeof redirect === 'string' && redirect.startsWith('/')) {
            return router.push(redirect);
        }

        router.push(authService.dashboardRoute());
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <section class="vh-100 item-center container">
        <Card class="card-login">
            <template #content>
                <div class="logo item-center">
                    <img :src="logo" alt="Logo">
                </div>
                <form @submit.prevent="onSubmit">
                    <div class="field mb-3">
                        <label for="email">E-mail</label>
                        <InputText
                            id="email"
                            type="email"
                            placeholder="seu@email.com"
                            v-model="form.email"
                            fluid
                            :invalid="!!fieldErrors.email"
                            @input="fieldErrors.email = null" 
                        />
                    </div>
                    <div class="field mb-3">
                        <label for="password">Senha</label>
                        <Password
                            id="password"
                            v-model="form.password"
                            placeholder="••••••••"
                            :feedback="false"
                            fluid
                            toggleMask
                            :invalid="!!fieldErrors.password"
                            @input="fieldErrors.password = null" 
                        />
                    </div>
                    <div>
                        <Checkbox 
                            v-model="form.remember_me" 
                            :binary="true" 
                            inputId="remember_me" 
                        />
                        <label for="remember_me" class="ms-2">Lembrar de mim</label>
                    </div>
                    <Button
                        type="submit"
                        :label="loading ? 'Aguarde...' : 'Entrar'"
                        :loading="loading"
                        icon="pi pi-sign-in"
                        fluid
                        class="mt-3"
                    />
                    <RouterLink to="" class="d-block fs-6 text-center link-primary text-decoration-none mt-3">
                        Esqueceu sua senha?
                    </RouterLink>
                </form>
            </template>
        </Card>
    </section>
</template>
<style scoped>
.card-login {
    width: 100%;
    max-width: 450px;
}

.logo img {
    width: 100%;
    max-width: 200px;
}
</style>