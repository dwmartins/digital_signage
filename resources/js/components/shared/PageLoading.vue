<script setup>
import { pageLoadingStore } from '@/stores/pageLoadingStore';
</script>

<template>
    <div v-if="pageLoadingStore.isLoading" class="page-loading">
        <div class="loader"></div>
        <p v-if="pageLoadingStore.message" class="text-secondary">{{ pageLoadingStore.message }}</p>
    </div>
</template>

<style scoped>
.page-loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 10px;
    z-index: 99999999;
}

.loader {
    width: 50px;
    padding: 8px;
    aspect-ratio: 1;
    border-radius: 50%;
    background: var(--primary-color);
    --_m:
        conic-gradient(#0000 10%, #000),
        linear-gradient(#000 0 0) content-box;
    -webkit-mask: var(--_m);
    mask: var(--_m);
    -webkit-mask-composite: source-out;
    mask-composite: subtract;
    animation: l3 0.6s infinite linear;
}

@keyframes l3 {
    to {
        transform: rotate(1turn)
    }
}
</style>