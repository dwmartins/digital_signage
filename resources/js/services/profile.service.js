import { API_URL } from "@/helpers/constants";
import axios from "axios";

export  default {
    /**
     * Atualiza a foto de perfil do usuário
     * 
     * @param {File} file 
     * @returns {Promise<{ message: string, data: {
     *      avatar: string,
     *      avatar_url: string
     * }}>}
     */
    async updateAvatar(file) {
        const payload = new FormData();
        payload.append('avatar', file);

        const response = await axios.post(`${API_URL}/profile/avatar`, payload, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        return response.data;
    },

    /**
     * Atualiza os dados básicos do usuário autenticado
     * 
     * @param {Object} data 
     * @returns {Promise<{ message: string, user: Object}>}
     */
    async update(data) {
        const response = await axios.put(`${API_URL}/profile`, data);
        return response.data;
    },

    /**
     * Atualiza as preferências de aparência do usuário autenticado.
     *
     * @param {Object} data
     * @returns {Promise<{ message: string, appearance_settings: Object}>}
     */
    async updateAppearance(data) {
        const response = await axios.put(`${API_URL}/profile/appearance`, data);
        return response.data;
    },

    /**
     * Restaura a aparência padrão do sistema.
     *
     * @returns {Promise<{ message: string, appearance_settings: Object}>}
     */
    async resetAppearance() {
        const response = await axios.post(`${API_URL}/profile/appearance/reset`);
        return response.data;
    },

    /**
     * Atualiza a senha do usuário autenticado
     * 
     * @param {Object} data 
     * @returns {Promise<{ message: string}>}
     */
    async updatePassword(data) {
        const response = await axios.put(`${API_URL}/profile/password`, data);
        return response.data;
    },

    /**
     * Retorna toda as sessões do usuário autenticado.
     * 
     * @returns {Promise<[]>}
     */
    async getSessions() {
        const response = await axios.get(`${API_URL}/profile/sessions`);
        return response.data;
    },

    /**
     * Remove uma sessão especifica
     * 
     * @param {Number} session_id 
     * @returns {Promise<{ message: string}>}
     */
    async removeSession(session_id) {
        const response = await axios.delete(`${API_URL}/profile/sessions/${session_id}`);
        return response.data;
    }
}
