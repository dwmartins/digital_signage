<script setup>
const props = defineProps({
    showBtnCleanFilters: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['clean-filters']);

const emptyImage = new URL('@assets/svg/empty.svg', import.meta.url).href;

</script>

<template>
    <div class="w-100 d-flex flex-column align-items-center mb-4">
        <img :src="emptyImage" alt="Vazio" class="mb-3">
        <h3 class="mb-0">Ops, nada por aqui...</h3>
        <p class="text-muted">Verifique os filtros aplicados ou tente ajustar sua busca.</p>

        <Button
            v-if="props.showBtnCleanFilters"
            severity="secondary"
            icon="pi pi-filter-slash"
            label="Limpar filtros"
            class="mt-3"
            @click="emit('clean-filters')"
        />
    </div>
</template>

<style scoped>
img {
    width: 100%;
    max-width: 200px;
}
</style>