import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /**
     * Lista os clientes anunciantes com paginação e filtros opcionais.
     *
     * @param {Number} page Página atual.
     * @param {Number} perPage Quantidade de registros por página.
     * @param {Object} filters Filtros enviados como query params.
     * @returns {Promise<{data: Object[], pagination: Object}>}
     */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/customer-users`, {
            params: { page, perPage, ...filters },
        });

        return response.data;
    },

    /**
     * Cria um cliente anunciante.
     *
     * @param {Object} data Dados do cliente anunciante.
     * @returns {Promise<{message: string, user: Object}>}
     */
    async create(data) {
        const response = await axios.post(`${API_URL}/customer-users`, data);
        return response.data;
    },

    /**
     * Atualiza um cliente anunciante.
     *
     * @param {Object} data Dados do cliente anunciante, incluindo o id.
     * @returns {Promise<{message: string, user: Object}>}
     */
    async update(data) {
        const response = await axios.put(`${API_URL}/customer-users/${data.id}`, data);
        return response.data;
    },

    /**
     * Exclui um cliente anunciante.
     *
     * @param {Number} id Identificador do cliente anunciante.
     * @returns {Promise<{message: string}>}
     */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/customer-users/${id}`);
        return response.data;
    },
};
