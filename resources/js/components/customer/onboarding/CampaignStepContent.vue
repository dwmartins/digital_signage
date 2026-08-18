<script setup>
defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div>
        <div class="mb-4">
            <span class="step-eyebrow">ETAPA 2</span>
            <h2 class="h4 fw-bold mb-2">Conte um pouco sobre sua campanha</h2>
            <p class="text-muted mb-0">Use um nome fácil de identificar e descreva o objetivo da divulgação.</p>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="field">
                    <label><span class="text-danger me-1">*</span>Nome da campanha</label>
                    <InputText
                        v-model="form.name"
                        maxlength="255"
                        placeholder="Ex.: Promoção de verão"
                        fluid
                    />
                    <small class="text-muted">Este nome será usado para identificar sua campanha.</small>
                </div>
            </div>

            <div class="col-12">
                <div class="field">
                    <label>Objetivo e observações</label>
                    <Textarea
                        v-model="form.description"
                        rows="6"
                        maxlength="5000"
                        placeholder="Explique o que deseja divulgar, o público e outras informações importantes..."
                        autoResize
                        fluid
                    />
                    <small class="d-block text-end text-muted">{{ form.description.length }}/5000</small>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.step-eyebrow {
    color: var(--p-primary-color);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
}

</style>
