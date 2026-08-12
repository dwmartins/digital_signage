import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /**
     * Lista os estabelecimentos com paginação e filtros opcionais.
     *
     * @param {Number} page Página atual.
     * @param {Number} perPage Quantidade de registros por página.
     * @param {Object} filters Filtros enviados como query params.
     * @returns {Promise<{data: Object[], pagination: Object}>}
     */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/establishments`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },

    /**
     * Cria um estabelecimento.
     *
     * @param {Object} data Dados do estabelecimento.
     * @returns {Promise<{message: string, establishment: Object}>}
     */
    async create(data) {
        const response = await axios.post(`${API_URL}/establishments`, data);
        return response.data;
    },

    /**
     * Atualiza um estabelecimento.
     *
     * @param {Object} data Dados do estabelecimento, incluindo o id.
     * @returns {Promise<{message: string, establishment: Object}>}
     */
    async update(data) {
        const response = await axios.put(`${API_URL}/establishments/${data.id}`, data);
        return response.data;
    },

    /**
     * Exclui um estabelecimento.
     *
     * @param {Number} id Identificador do estabelecimento.
     * @returns {Promise<{message: string}>}
     */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/establishments/${id}`);
        return response.data;
    },
};
