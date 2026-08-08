import { updatePrimaryPalette, updateSurfacePalette, usePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';
import Lara from '@primeuix/themes/lara';
import Material from '@primeuix/themes/material';
import Nora from '@primeuix/themes/nora';

const APPEARANCE_STORAGE_KEY = 'appearance-settings';
const DARK_MODE_STORAGE_KEY = 'dark-mode';

export const DEFAULT_APPEARANCE = {
    preset: 'aura',
    primary: 'primary',
    surface: 'slate',
    dark_mode: false,
};

export const THEME_PRESETS = [
    { label: 'Aura', value: 'aura' },
    { label: 'Lara', value: 'lara' },
    { label: 'Nora', value: 'nora' },
    { label: 'Material', value: 'material' },
];

export const PRIMARY_PALETTES = [
    {
        label: 'Primário',
        value: 'primary',
        preview: '#008e96',
        colors: {
            50: '#ecfeff',
            100: '#cffafe',
            200: '#a5f3fc',
            300: '#67e8f9',
            400: '#22d3ee',
            500: '#008e96',
            600: '#087982',
            700: '#0f626a',
            800: '#155158',
            900: '#16454b',
            950: '#082f34',
        },
    },
    {
        label: 'Emerald',
        value: 'emerald',
        preview: '#10b981',
        colors: {
            50: '#ecfdf5',
            100: '#d1fae5',
            200: '#a7f3d0',
            300: '#6ee7b7',
            400: '#34d399',
            500: '#10b981',
            600: '#059669',
            700: '#047857',
            800: '#065f46',
            900: '#064e3b',
            950: '#022c22',
        },
    },
    {
        label: 'Green',
        value: 'green',
        preview: '#22c55e',
        colors: {
            50: '#f0fdf4',
            100: '#dcfce7',
            200: '#bbf7d0',
            300: '#86efac',
            400: '#4ade80',
            500: '#22c55e',
            600: '#16a34a',
            700: '#15803d',
            800: '#166534',
            900: '#14532d',
            950: '#052e16',
        },
    },
    {
        label: 'Lime',
        value: 'lime',
        preview: '#84cc16',
        colors: {
            50: '#f7fee7',
            100: '#ecfccb',
            200: '#d9f99d',
            300: '#bef264',
            400: '#a3e635',
            500: '#84cc16',
            600: '#65a30d',
            700: '#4d7c0f',
            800: '#3f6212',
            900: '#365314',
            950: '#1a2e05',
        },
    },
    {
        label: 'Red',
        value: 'red',
        preview: '#ef4444',
        colors: {
            50: '#fef2f2',
            100: '#fee2e2',
            200: '#fecaca',
            300: '#fca5a5',
            400: '#f87171',
            500: '#ef4444',
            600: '#dc2626',
            700: '#b91c1c',
            800: '#991b1b',
            900: '#7f1d1d',
            950: '#450a0a',
        },
    },
    {
        label: 'Orange',
        value: 'orange',
        preview: '#f97316',
        colors: {
            50: '#fff7ed',
            100: '#ffedd5',
            200: '#fed7aa',
            300: '#fdba74',
            400: '#fb923c',
            500: '#f97316',
            600: '#ea580c',
            700: '#c2410c',
            800: '#9a3412',
            900: '#7c2d12',
            950: '#431407',
        },
    },
    {
        label: 'Amber',
        value: 'amber',
        preview: '#f59e0b',
        colors: {
            50: '#fffbeb',
            100: '#fef3c7',
            200: '#fde68a',
            300: '#fcd34d',
            400: '#fbbf24',
            500: '#f59e0b',
            600: '#d97706',
            700: '#b45309',
            800: '#92400e',
            900: '#78350f',
            950: '#451a03',
        },
    },
    {
        label: 'Yellow',
        value: 'yellow',
        preview: '#eab308',
        colors: {
            50: '#fefce8',
            100: '#fef9c3',
            200: '#fef08a',
            300: '#fde047',
            400: '#facc15',
            500: '#eab308',
            600: '#ca8a04',
            700: '#a16207',
            800: '#854d0e',
            900: '#713f12',
            950: '#422006',
        },
    },
    {
        label: 'Teal',
        value: 'teal',
        preview: '#14b8a6',
        colors: {
            50: '#f0fdfa',
            100: '#ccfbf1',
            200: '#99f6e4',
            300: '#5eead4',
            400: '#2dd4bf',
            500: '#14b8a6',
            600: '#0d9488',
            700: '#0f766e',
            800: '#115e59',
            900: '#134e4a',
            950: '#042f2e',
        },
    },
    {
        label: 'Cyan',
        value: 'cyan',
        preview: '#06b6d4',
        colors: {
            50: '#ecfeff',
            100: '#cffafe',
            200: '#a5f3fc',
            300: '#67e8f9',
            400: '#22d3ee',
            500: '#06b6d4',
            600: '#0891b2',
            700: '#0e7490',
            800: '#155e75',
            900: '#164e63',
            950: '#083344',
        },
    },
    {
        label: 'Sky',
        value: 'sky',
        preview: '#0ea5e9',
        colors: {
            50: '#f0f9ff',
            100: '#e0f2fe',
            200: '#bae6fd',
            300: '#7dd3fc',
            400: '#38bdf8',
            500: '#0ea5e9',
            600: '#0284c7',
            700: '#0369a1',
            800: '#075985',
            900: '#0c4a6e',
            950: '#082f49',
        },
    },
    {
        label: 'Blue',
        value: 'blue',
        preview: '#3b82f6',
        colors: {
            50: '#eff6ff',
            100: '#dbeafe',
            200: '#bfdbfe',
            300: '#93c5fd',
            400: '#60a5fa',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8',
            800: '#1e40af',
            900: '#1e3a8a',
            950: '#172554',
        },
    },
    {
        label: 'Indigo',
        value: 'indigo',
        preview: '#6366f1',
        colors: {
            50: '#eef2ff',
            100: '#e0e7ff',
            200: '#c7d2fe',
            300: '#a5b4fc',
            400: '#818cf8',
            500: '#6366f1',
            600: '#4f46e5',
            700: '#4338ca',
            800: '#3730a3',
            900: '#312e81',
            950: '#1e1b4b',
        },
    },
    {
        label: 'Violet',
        value: 'violet',
        preview: '#8b5cf6',
        colors: {
            50: '#f5f3ff',
            100: '#ede9fe',
            200: '#ddd6fe',
            300: '#c4b5fd',
            400: '#a78bfa',
            500: '#8b5cf6',
            600: '#7c3aed',
            700: '#6d28d9',
            800: '#5b21b6',
            900: '#4c1d95',
            950: '#2e1065',
        },
    },
    {
        label: 'Purple',
        value: 'purple',
        preview: '#a855f7',
        colors: {
            50: '#faf5ff',
            100: '#f3e8ff',
            200: '#e9d5ff',
            300: '#d8b4fe',
            400: '#c084fc',
            500: '#a855f7',
            600: '#9333ea',
            700: '#7e22ce',
            800: '#6b21a8',
            900: '#581c87',
            950: '#3b0764',
        },
    },
    {
        label: 'Fuchsia',
        value: 'fuchsia',
        preview: '#d946ef',
        colors: {
            50: '#fdf4ff',
            100: '#fae8ff',
            200: '#f5d0fe',
            300: '#f0abfc',
            400: '#e879f9',
            500: '#d946ef',
            600: '#c026d3',
            700: '#a21caf',
            800: '#86198f',
            900: '#701a75',
            950: '#4a044e',
        },
    },
    {
        label: 'Pink',
        value: 'pink',
        preview: '#ec4899',
        colors: {
            50: '#fdf2f8',
            100: '#fce7f3',
            200: '#fbcfe8',
            300: '#f9a8d4',
            400: '#f472b6',
            500: '#ec4899',
            600: '#db2777',
            700: '#be185d',
            800: '#9d174d',
            900: '#831843',
            950: '#500724',
        },
    },
    {
        label: 'Rose',
        value: 'rose',
        preview: '#f43f5e',
        colors: {
            50: '#fff1f2',
            100: '#ffe4e6',
            200: '#fecdd3',
            300: '#fda4af',
            400: '#fb7185',
            500: '#f43f5e',
            600: '#e11d48',
            700: '#be123c',
            800: '#9f1239',
            900: '#881337',
            950: '#4c0519',
        },
    },
];

export const SURFACE_PALETTES = [
    {
        label: 'Slate',
        value: 'slate',
        preview: '#64748b',
        colors: {
            0: '#ffffff',
            50: '#f8fafc',
            100: '#f1f5f9',
            200: '#e2e8f0',
            300: '#cbd5e1',
            400: '#94a3b8',
            500: '#64748b',
            600: '#475569',
            700: '#334155',
            800: '#1e293b',
            900: '#0f172a',
            950: '#020617',
        },
    },
    {
        label: 'Zinc',
        value: 'zinc',
        preview: '#71717a',
        colors: {
            0: '#ffffff',
            50: '#fafafa',
            100: '#f4f4f5',
            200: '#e4e4e7',
            300: '#d4d4d8',
            400: '#a1a1aa',
            500: '#71717a',
            600: '#52525b',
            700: '#3f3f46',
            800: '#27272a',
            900: '#18181b',
            950: '#09090b',
        },
    },
    {
        label: 'Neutral',
        value: 'neutral',
        preview: '#737373',
        colors: {
            0: '#ffffff',
            50: '#fafafa',
            100: '#f5f5f5',
            200: '#e5e5e5',
            300: '#d4d4d4',
            400: '#a3a3a3',
            500: '#737373',
            600: '#525252',
            700: '#404040',
            800: '#262626',
            900: '#171717',
            950: '#0a0a0a',
        },
    },
    {
        label: 'Gray',
        value: 'gray',
        preview: '#6b7280',
        colors: {
            0: '#ffffff',
            50: '#f9fafb',
            100: '#f3f4f6',
            200: '#e5e7eb',
            300: '#d1d5db',
            400: '#9ca3af',
            500: '#6b7280',
            600: '#4b5563',
            700: '#374151',
            800: '#1f2937',
            900: '#111827',
            950: '#030712',
        },
    },
    {
        label: 'Stone',
        value: 'stone',
        preview: '#78716c',
        colors: {
            0: '#ffffff',
            50: '#fafaf9',
            100: '#f5f5f4',
            200: '#e7e5e4',
            300: '#d6d3d1',
            400: '#a8a29e',
            500: '#78716c',
            600: '#57534e',
            700: '#44403c',
            800: '#292524',
            900: '#1c1917',
            950: '#0c0a09',
        },
    },
];

const presets = {
    aura: Aura,
    lara: Lara,
    nora: Nora,
    material: Material,
};

export const getDefaultAppearance = () => ({ ...DEFAULT_APPEARANCE });

export const normalizeAppearance = (settings = {}) => {
    const normalizedSettings = {
        ...settings,
        primary: settings?.primary === 'homecare' ? DEFAULT_APPEARANCE.primary : settings?.primary,
    };

    const primary = PRIMARY_PALETTES.some(item => item.value === normalizedSettings?.primary)
        ? normalizedSettings.primary
        : DEFAULT_APPEARANCE.primary;

    const surface = SURFACE_PALETTES.some(item => item.value === normalizedSettings?.surface)
        ? normalizedSettings.surface
        : DEFAULT_APPEARANCE.surface;

    const preset = THEME_PRESETS.some(item => item.value === normalizedSettings?.preset)
        ? normalizedSettings.preset
        : DEFAULT_APPEARANCE.preset;

    return {
        preset,
        primary,
        surface,
        dark_mode: typeof normalizedSettings?.dark_mode === 'boolean'
            ? normalizedSettings.dark_mode
            : DEFAULT_APPEARANCE.dark_mode,
    };
};

export const applyAppearance = (settings = {}, options = {}) => {
    const appearance = normalizeAppearance(settings);
    const primaryPalette = PRIMARY_PALETTES.find(item => item.value === appearance.primary);
    const surfacePalette = SURFACE_PALETTES.find(item => item.value === appearance.surface);

    usePreset(presets[appearance.preset] ?? Aura);
    updatePrimaryPalette(primaryPalette.colors);
    updateSurfacePalette(surfacePalette.colors);
    applyCssVariables(primaryPalette.colors, surfacePalette.colors, appearance.dark_mode);
    applyDarkMode(appearance.dark_mode);

    if (options.persistLocal !== false) {
        localStorage.setItem(APPEARANCE_STORAGE_KEY, JSON.stringify(appearance));
        localStorage.setItem(DARK_MODE_STORAGE_KEY, String(appearance.dark_mode));
    }

    return appearance;
};

/**
 * Alterna apenas o modo claro/escuro mantendo as demais preferências.
 */
export const toggleThemeHelper = (currentAppearance = {}) => {
    const appearance = normalizeAppearance(currentAppearance);
    return applyAppearance({
        ...appearance,
        dark_mode: !appearance.dark_mode,
    });
};

/**
 * Inicializa a aparência antes da aplicação montar.
 */
export const initTheme = () => {
    const storedAppearance = readStoredAppearance();

    if (storedAppearance) {
        return applyAppearance(storedAppearance);
    }

    const savedMode = localStorage.getItem(DARK_MODE_STORAGE_KEY);
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const darkMode = savedMode ? savedMode === 'true' : systemPrefersDark;

    return applyAppearance({
        ...DEFAULT_APPEARANCE,
        dark_mode: darkMode,
    });
};

const readStoredAppearance = () => {
    try {
        const stored = localStorage.getItem(APPEARANCE_STORAGE_KEY);
        return stored ? JSON.parse(stored) : null;
    } catch {
        return null;
    }
};

const applyDarkMode = (isDarkMode) => {
    document.documentElement.classList.toggle('dark-mode', isDarkMode);
};

const applyCssVariables = (primaryColors, surfaceColors, isDarkMode) => {
    const root = document.documentElement;
    const pageBackground = isDarkMode ? surfaceColors[950] : surfaceColors[100];
    const contentBackground = isDarkMode ? surfaceColors[900] : surfaceColors[0];
    const textColor = isDarkMode ? surfaceColors[50] : surfaceColors[700];
    const textMutedColor = isDarkMode ? surfaceColors[300] : surfaceColors[500];

    Object.entries(primaryColors).forEach(([shade, color]) => {
        root.style.setProperty(`--p-primary-${shade}`, color);
    });

    Object.entries(surfaceColors).forEach(([shade, color]) => {
        root.style.setProperty(`--p-surface-${shade}`, color);
    });

    root.style.setProperty('--primary-color', primaryColors[500]);
    root.style.setProperty('--sidebar-bg', isDarkMode ? surfaceColors[900] : primaryColors[950]);
    root.style.setProperty('--cards-dark', surfaceColors[900]);
    root.style.setProperty('--body-dark', surfaceColors[950]);
    root.style.setProperty('--p-card-color', textColor);
    root.style.setProperty('--app-page-bg', pageBackground);
    root.style.setProperty('--app-content-bg', contentBackground);
    root.style.setProperty('--app-header-bg', contentBackground);
    root.style.setProperty('--app-border-color', isDarkMode ? surfaceColors[800] : surfaceColors[200]);
    root.style.setProperty('--breadcrumb-color', textMutedColor);
    root.style.setProperty('--breadcrumb-color-hover', textColor);
    root.style.setProperty('--text-title', isDarkMode ? surfaceColors[0] : surfaceColors[900]);
};
