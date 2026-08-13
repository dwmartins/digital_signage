<script setup>
import { computed } from 'vue';

const props = defineProps({ modelValue: Boolean, media: Object });
const emit = defineEmits(['update:modelValue']);
const visible = computed({ get: () => props.modelValue, set: value => emit('update:modelValue', value) });
</script>

<template>
    <Dialog v-model:visible="visible" modal :style="{ width: '72rem' }" :breakpoints="{ '992px': '94vw' }" :draggable="false" :header="media?.name || 'Visualizar mídia'">
        <div class="media-preview-container">
            <img v-if="media?.type === 'image'" :src="media.content_url" :alt="media.name" class="media-preview" />
            <video v-else-if="media" :src="media.content_url" class="media-preview" controls preload="metadata"></video>
        </div>
    </Dialog>
</template>

<style scoped>
.media-preview-container { display: flex; justify-content: center; min-height: 240px; background: #0f172a; border-radius: 8px; overflow: hidden; }
.media-preview { display: block; max-width: 100%; max-height: 72vh; object-fit: contain; }
</style>
