import axios from "axios";
import authService from "./services/auth.service";
import router from "./router";

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['Accept-Language'] = localStorage.getItem('locale') || 'pt';

// Interceptador de resposta para tratar erros globais
axios.interceptors.response.use(
    response => response,
    error => {
        const forceLogout = error.response?.data?.force_logout === true;
        const unauthenticated = error.response?.status === 401;
        
        if (unauthenticated || forceLogout) {
            authService.clearAuth();

            // Verifica se a requisição que falhou foi a de validação de sessão (/auth/validate)
            // Isso é crucial para evitar loops infinitos de redirecionamento
            const isValidateRequest = error.config.url.endsWith('/auth/validate');

            // Redireciona para o login apenas se:
            // 1. O usuário ainda não estiver na página de login
            // 2. A requisição que falhou NÃO for a de validação automática (pois o router guard já lida com isso)
            if (router.currentRoute.value.name !== 'login' && !isValidateRequest) {
                router.push({ name: 'login' });
            }
        }

        return Promise.reject(error);
    }
);

// Verifique se a solicitação tem baixa prioridade.
axios.interceptors.request.use(config => {
    if (config.meta?.lowPriority) {
        return new Promise(resolve => {
            // throw to the end of the event queue
            setTimeout(() => resolve(config), 200);
        });
    }
    return config;
});
