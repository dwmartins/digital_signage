import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /**
     * Lista as categorias com paginação e filtros opcionais.
     *
     * @param {Number} page Página atual.
     * @param {Number} perPage Quantidade de registros por página.
     * @param {Object} filters Filtros enviados como query params.
     * @returns {Promise<{data: Object[], pagination: Object}>}
     */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/categories`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },

    /**
     * Cria uma categoria.
     *
     * @param {Object} data Dados da categoria.
     * @returns {Promise<{message: string, category: Object}>}
     */
    async create(data) {
        const response = await axios.post(`${API_URL}/categories`, data);
        return response.data;
    },

    /**
     * Atualiza uma categoria.
     *
     * @param {Object} data Dados da categoria, incluindo o id.
     * @returns {Promise<{message: string, category: Object}>}
     */
    async update(data) {
        const response = await axios.put(`${API_URL}/categories/${data.id}`, data);
        return response.data;
    },

    /**
     * Exclui uma categoria.
     *
     * @param {Number} id Identificador da categoria.
     * @returns {Promise<{message: string}>}
     */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/categories/${id}`);
        return response.data;
    },
};
