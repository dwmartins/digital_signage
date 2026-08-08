import './bootstrap';
import './axios';

import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';

import 'notyf/notyf.min.css';

import 'bootstrap/dist/css/bootstrap-utilities.min.css';
import 'bootstrap/dist/css/bootstrap-grid.min.css';
import '../css/overwrite-bootstrap.css';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import { initTheme } from './helpers/theme';
import Tooltip from 'primevue/tooltip';
import { pt } from './locales/primevue/pt';
import 'primeicons/primeicons.css';

import PageLoading from '@/components/shared/PageLoading.vue';

const pageLoading = createApp(PageLoading);
pageLoading.mount('#pageLoading');

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
// Os guards do roteador usam as stores de autenticação e tema. O Pinia
// precisa estar ativo antes de o roteador iniciar a primeira navegação.
app.use(router);

app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.dark-mode',
        }
    },
    locale: pt
});

app.directive('tooltip', Tooltip);

initTheme();

app.mount('#app');