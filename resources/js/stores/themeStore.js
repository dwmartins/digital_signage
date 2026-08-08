import {
    applyAppearance,
    getDefaultAppearance,
    initTheme,
    normalizeAppearance,
    toggleThemeHelper,
} from '@/helpers/theme';
import profileService from '@/services/profile.service';
import { defineStore } from 'pinia';
import { computed, reactive } from 'vue';

/**
 * Pinia para gerenciar a aparência do aplicativo.
 */
export const useThemeStore = defineStore('theme', () => {
    const appearance = reactive(initTheme());
    const is_dark = computed(() => appearance.dark_mode);

    /**
     * Aplica uma aparência sem persistir no banco.
     */
    const setAppearance = (settings = {}, options = {}) => {
        Object.assign(appearance, applyAppearance(settings, {
            persistLocal: options.persistLocal ?? true,
        }));
    };

    /**
     * Pré-visualiza uma aparência enquanto o usuário edita o formulário.
     */
    const previewAppearance = (settings = {}) => {
        Object.assign(appearance, applyAppearance(settings, { persistLocal: false }));
    };

    /**
     * Salva as preferências visuais no banco.
     */
    const saveAppearance = async (settings = {}) => {
        const payload = normalizeAppearance(settings);
        const response = await profileService.updateAppearance(payload);

        setAppearance(response.appearance_settings ?? payload);

        return response;
    };

    /**
     * Restaura o padrão visual do sistema e persiste no banco.
     */
    const resetAppearance = async () => {
        const response = await profileService.resetAppearance();

        setAppearance(response.appearance_settings ?? getDefaultAppearance());

        return response;
    };

    /**
     * Alterna entre os temas claro e escuro e persiste a preferência.
     */
    const toggleTheme = async () => {
        const nextAppearance = toggleThemeHelper(appearance);

        Object.assign(appearance, nextAppearance);

        const response = await profileService.updateAppearance(nextAppearance);

        setAppearance(response.appearance_settings ?? nextAppearance);

        return response;
    };

    return {
        appearance,
        is_dark,
        setAppearance,
        previewAppearance,
        saveAppearance,
        resetAppearance,
        toggleTheme,
    };
});
