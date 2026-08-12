import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /**
     * Lista as telas com paginação e filtros opcionais.
     *
     * @param {Number} page Página atual.
     * @param {Number} perPage Quantidade de registros por página.
     * @param {Object} filters Filtros enviados como query params.
     * @returns {Promise<{data: Object[], pagination: Object}>}
     */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/screens`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },

    /**
     * Retorna os estabelecimentos disponíveis para seleção.
     *
     * @returns {Promise<{data: Object[]}>}
     */
    async establishmentOptions() {
        const response = await axios.get(`${API_URL}/screens/establishment-options`);
        return response.data;
    },

    /**
     * Cria uma tela.
     *
     * @param {Object} data Dados da tela.
     * @returns {Promise<{message: string, screen: Object}>}
     */
    async create(data) {
        const response = await axios.post(`${API_URL}/screens`, data);
        return response.data;
    },

    /**
     * Atualiza uma tela.
     *
     * @param {Object} data Dados da tela, incluindo o id.
     * @returns {Promise<{message: string, screen: Object}>}
     */
    async update(data) {
        const response = await axios.put(`${API_URL}/screens/${data.id}`, data);
        return response.data;
    },

    /**
     * Exclui uma tela.
     *
     * @param {Number} id Identificador da tela.
     * @returns {Promise<{message: string}>}
     */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/screens/${id}`);
        return response.data;
    },
};
