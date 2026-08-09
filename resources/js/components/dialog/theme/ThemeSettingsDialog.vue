<script setup>
import {
    PRIMARY_PALETTES,
    SURFACE_PALETTES,
    THEME_PRESETS,
    getDefaultAppearance,
} from '@/helpers/theme';
import { showAlert } from '@/helpers/alert';
import { useThemeStore } from '@/stores/themeStore';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    modelValue: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const themeStore = useThemeStore();

const saving = ref(false);
const saved = ref(false);
const originalAppearance = ref(getDefaultAppearance());

const form = reactive(getDefaultAppearance());

const visible = computed({
    get: () => props.modelValue,
    set: (value) => {
        if (value) {
            emit('update:modelValue', true);
            return;
        }

        closeDialog();
    },
});

const selectedPrimary = computed(() => {
    return PRIMARY_PALETTES.find(item => item.value === form.primary);
});

const selectedSurface = computed(() => {
    return SURFACE_PALETTES.find(item => item.value === form.surface);
});

const fillForm = (settings) => {
    Object.assign(form, {
        ...getDefaultAppearance(),
        ...(settings || {}),
    });
};

const closeDialog = () => {
    if (!saved.value) {
        themeStore.previewAppearance(originalAppearance.value);
    }

    emit('update:modelValue', false);
};

const onSubmit = async () => {
    try {
        saving.value = true;
        const response = await themeStore.saveAppearance(form);

        saved.value = true;
        showAlert('success', response.message);
        emit('update:modelValue', false);
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

const onReset = async () => {
    try {
        saving.value = true;
        const response = await themeStore.resetAppearance();

        originalAppearance.value = { ...themeStore.appearance };
        fillForm(themeStore.appearance);
        showAlert('success', response.message);
    } catch (error) {
        showAlert('error', error.response?.data);
    } finally {
        saving.value = false;
    }
};

watch(() => props.modelValue, (opened) => {
    if (!opened) return;

    saved.value = false;
    originalAppearance.value = { ...themeStore.appearance };
    fillForm(themeStore.appearance);
});

watch(form, () => {
    if (!props.modelValue || saving.value) return;

    themeStore.previewAppearance(form);
}, { deep: true });
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :style="{ width: '42rem' }"
        :breakpoints="{ '768px': '94vw' }"
        :draggable="false"
        header="Personalizar aparência"
    >
        <form id="themeSettingsForm" class="row g-4" @submit.prevent="onSubmit">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center gap-3 border rounded p-3">
                    <div>
                        <span class="fw-semibold d-block">Modo escuro</span>
                        <small class="text-muted">Alterna a interface entre claro e escuro.</small>
                    </div>
                    <ToggleSwitch v-model="form.dark_mode" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="field">
                    <label for="preset">Tema</label>
                    <Select
                        id="preset"
                        v-model="form.preset"
                        :options="THEME_PRESETS"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                    />
                </div>
            </div>

            <div class="col-md-6">
                <div class="field">
                    <label>Prévia</label>
                    <div class="theme-preview border rounded">
                        <span class="theme-preview__primary" :style="{ backgroundColor: selectedPrimary?.preview }"></span>
                        <span class="theme-preview__surface" :style="{ backgroundColor: selectedSurface?.preview }"></span>
                        <Button label="Botão" size="small" />
                    </div>
                </div>
            </div>

            <div class="col-12">
                <Divider align="left" type="solid">
                    <b>Cor primária</b>
                </Divider>

                <div class="row g-2">
                    <div
                        v-for="palette in PRIMARY_PALETTES"
                        :key="palette.value"
                        class="col-6 col-sm-4 col-lg-3"
                    >
                        <button
                            type="button"
                            class="palette-option"
                            :class="{ active: form.primary === palette.value }"
                            @click="form.primary = palette.value"
                        >
                            <span class="palette-option__swatch" :style="{ backgroundColor: palette.preview }"></span>
                            <span>{{ palette.label }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <Divider align="left" type="solid">
                    <b>Surface</b>
                </Divider>

                <div class="row g-2">
                    <div
                        v-for="palette in SURFACE_PALETTES"
                        :key="palette.value"
                        class="col-6 col-sm-4"
                    >
                        <button
                            type="button"
                            class="palette-option"
                            :class="{ active: form.surface === palette.value }"
                            @click="form.surface = palette.value"
                        >
                            <span class="palette-option__swatch" :style="{ backgroundColor: palette.preview }"></span>
                            <span>{{ palette.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <Button
                label="Voltar ao padrão"
                icon="pi pi-undo"
                severity="secondary"
                outlined
                :disabled="saving"
                @click="onReset"
            />
            <Button
                label="Cancelar"
                icon="pi pi-times"
                class="p-button-text"
                severity="secondary"
                :disabled="saving"
                @click="closeDialog"
            />
            <Button
                :label="saving ? 'Salvando...' : 'Salvar aparência'"
                icon="pi pi-check"
                type="submit"
                form="themeSettingsForm"
                :loading="saving"
            />
        </template>
    </Dialog>
</template>

<style scoped>
.theme-preview {
    min-height: 42px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px;
}

.theme-preview__primary,
.theme-preview__surface,
.palette-option__swatch {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    flex: 0 0 24px;
    border: 1px solid rgba(15, 23, 42, 0.12);
}

.palette-option {
    width: 100%;
    min-height: 42px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--p-content-border-color, #e5e7eb);
    border-radius: 8px;
    background: var(--p-content-background, #ffffff);
    color: var(--p-text-color, #334155);
    padding: 8px 10px;
    font: inherit;
    text-align: left;
}

.palette-option.active {
    border-color: var(--p-primary-500);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--p-primary-500) 18%, transparent);
}
</style>
