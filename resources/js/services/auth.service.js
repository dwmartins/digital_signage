import { API_URL } from "@/helpers/constants";
import { useAuthStore } from "@/stores/authStore";
import { useThemeStore } from "@/stores/themeStore";
import axios from "axios";

export default {
    /**
     * Valida no backend se o usuário realmente está autenticado.
     * @returns {Promise<{ 
     *      message: string, 
     *      is_valid: boolean, 
     *      user: Object|null 
     * }>}
     */
    async validate() {
        try {
            await axios.get('/sanctum/csrf-cookie', {
                withCredentials: true
            });

            const { data } = await axios.get(`${API_URL}/auth/validate`, {
                withCredentials: true
            });

            this.setUserAuthenticate(data.user, data.auth);
            this.addSettingsToAxios();
            return data;
        } catch (error) {
            this.clearAuth();

            return { 
                message: null,
                is_valid: false, 
                user: null 
            };
        }
    },

    /**
     * Realiza o login do usuário.
     *
     * @param {Object} userData
     * @param {string} userData.email - E-mail do usuário
     * @param {string} userData.password - Senha do usuário
     * @param {boolean} userData.remember_me - Mantém a sessão ativa
     *
     * @returns {Promise<{ message: string, user: Object|null }>}
     */
    async login(userData) {
        // Gerar o CSRF antes do login
        await axios.get(`/sanctum/csrf-cookie`, {
            withCredentials: true
        });

        const { data } = await axios.post(`${API_URL}/login`, userData);

        localStorage.setItem('last_email', userData.email);

        this.setUserAuthenticate(data.user, data.auth);
        this.addSettingsToAxios();
        return data;
    },

    /**
     * Retorna a rota inicial adequada para o usuário autenticado.
     * @returns {{name: string}}
     */
    dashboardRoute() {
        const authStore = useAuthStore();

        if (authStore.isPlatformUser()) {
            return { name: 'platform.dashboard' };
        }

        return { name: 'company.dashboard' };
    },

    /**
     * Atualiza na store o usuário autenticado.
     * @param {Object} user
     * @param {Object} auth
     * @returns {void}
     */
    setUserAuthenticate(user, auth = {}) {
        const authStore  = useAuthStore();
        const themeStore = useThemeStore();

        authStore.update(user);
        authStore.updatePermissions(auth);

        if (user?.appearance_settings) {
            themeStore.setAppearance(user.appearance_settings, { persistLocal: true });
        }

        authStore.setIsAuthenticate(true);
    },

    /**
     * Configura o axios para sempre enviar withCredentials
     * @returns {void}
     */
    addSettingsToAxios() {
        axios.defaults.withCredentials = true;
    },

    /**
     * Limpa todos os dados relacionado ao usuário autenticado.
     * @returns {void}
     */
    clearAuth() {
        const authStore = useAuthStore();
        authStore.clean();
        delete axios.defaults.headers.common['X-Company-Id'];
    },

    /**
     * Realiza o logou e limpa todos os dados relacionado ao usuário autenticado.
     * 
     * @returns {Promise<any>}
     */
    async logout() {
        const { data } = await axios.post(`${API_URL}/logout`);
        this.clearAuth()
        return data;
    },
}
