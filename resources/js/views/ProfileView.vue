<script setup>
import BasicInfoForm from '@/components/form/profile/BasicInfoForm.vue';
import CardResumeForm from '@/components/form/profile/CardResumeForm.vue';
import PasswordForm from '@/components/form/profile/PasswordForm.vue';
import SessionsForm from '@/components/form/profile/SessionsForm.vue';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';

const breadcrumbItens = [
    { icon: 'pi pi-home', to: '/' },
    { label: 'Perfil' },
];
</script>

<template>
    <section class="container">
        <Breadcrumb :items="breadcrumbItens"/>

        <div class="row g-4">
            <div class="col-lg-4"> 
                <CardResumeForm/>
            </div>

            <div class="col-lg-8 d-flex flex-column gap-4">
                <BasicInfoForm/>
                <PasswordForm/>
                <SessionsForm/>
            </div>
        </div>
    </section>
</template>