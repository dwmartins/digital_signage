<script setup>
defineProps({
    form: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
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
            <div class="col-12 col-lg-7">
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

            <div class="col-12 col-lg-5">
                <div class="field">
                    <label>Categorias</label>
                    <MultiSelect
                        v-model="form.category_ids"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Selecione, se desejar"
                        filter
                        display="chip"
                        fluid
                    />
                    <small class="text-muted">Ajuda a classificar o conteúdo da campanha.</small>
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

.field {
    min-width: 0;
}

:deep(.p-multiselect) {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

:deep(.p-multiselect-label-container) {
    min-width: 0;
}

:deep(.p-multiselect-label) {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    min-width: 0;
    white-space: normal;
}

:deep(.p-multiselect-chip) {
    max-width: 100%;
}

:deep(.p-multiselect-chip .p-chip-label) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
