import { ROLE_ADMIN, ROLE_CUSTOMER, ROLE_SUPPORT } from "@/helpers/constants";
import { defineStore } from "pinia";
import { reactive, ref } from "vue";

function defaultUser() {
    return {
        id: null,
        name: null,
        last_name: null,
        full_name: null,
        email: null,
        phone: null,
        description: null,
        email_verified_at: null,
        birth_date: null,
        avatar: null,
        avatar_url: null,
        appearance_settings: null,
        role: null,
        status: null,
        last_login_at: null,
        created_at: null,
        updated_at: null,
        permissions: [],
    }
}

export const useAuthStore = defineStore('user', () => {
    const user = reactive(defaultUser());
    const isLoggedIn = ref(false);

    /**
     * Retorna os dados do usuário autenticado
     * @returns {Object}
     */
    function getUser() {
        return user;
    }

    /**
     * Altera o status de logado
     * @param {Boolean} value
     */
    function setIsAuthenticate(value) {
        isLoggedIn.value = value;
    }

    /**
     * Retorna se o usuário está autenticado.
     * @returns {boolean}
     */
    function isAuthenticate() {
        return isLoggedIn.value;
    }

    /**
     * Atualiza os dados do usuário com as informações fornecidas.
     * @param {Object} data - Objeto contendo novos dados do usuário.
     */
    function update(data) {
        if (!data || typeof data !== 'object') return;

        Object.assign(user, data);

        if(data.birth_date) {
            user.birth_date = new Date(user.birth_date + 'T00:00:00');
        }
    }

    /**
     * Atualiza o contexto de autorização do usuário.
     * @param {Object} data
     */
    function updatePermissions(data = {}) {
        user.permissions = Array.isArray(data.permissions) ? data.permissions : [];
    }

    /**
     * Atualiza o avatar do usuário
     * @param {String} avatar
     * @param {String} avatar_url
     */
    function updateAvatar(avatar, avatar_url) {
        user.avatar = avatar;
        user.avatar_url = avatar_url;
    }

    /**
     * Limpa todos os dados do usuário, definindo-os como nulos, e redefine o status de login.
     */
    function clean() {
        Object.assign(user, defaultUser());

        setIsAuthenticate(false);
    }

    /**
     * Retorna a função do usuário com a primeira letra maiúscula.
     * @returns {string}
     */
    function getRole() {
        const role = user.role;
        let role_name = "";

        switch (role) {
            case ROLE_ADMIN:
                role_name = 'Administrador';
                break;
            case ROLE_SUPPORT:
                role_name = 'Suporte';
                break;
            case ROLE_CUSTOMER:
                role_name = 'Cliente';
                break;
            default:
                break;
        }

        return role_name;
    }

    /**
     * Verifica se é um admin
     */
    function isAdmin() {
        return user.role === ROLE_ADMIN;
    }

    /**
     * Verifica se é um suporte
     */
    function isSupport() {
        return user.role === ROLE_SUPPORT;
    }

    /**
     * Verifica se é um cliente
     */
    function isCustomer() {
        return user.role === ROLE_CUSTOMER;
    }

    /**
     * Verifica se o usuário possui uma permissão.
     * @param {string} permission
     * @returns {boolean}
     */
    function hasPermission(permission) {
        return user.permissions.includes(permission);
    }

    return {
        user,
        getUser,
        setIsAuthenticate,
        isAuthenticate,
        update,
        updatePermissions,
        updateAvatar,
        clean,
        getRole,
        hasPermission,
        isAdmin,
        isSupport,
        isCustomer
    }
});