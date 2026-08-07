import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import path from 'path';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/notyf-theme.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vueDevTools({
            // Laravel renderiza Blade, entao o DevTools precisa ser injetado no entrypoint da aplicacao.
            appendTo: 'resources/js/app.js',
        }),
        vue(),
        Components({
            resolvers: [PrimeVueResolver()]
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@assets': path.resolve(__dirname, 'resources/assets')
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        assetsDir: '.',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                assetFileNames: '[name][extname]',
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
            },
        },
    },
});
