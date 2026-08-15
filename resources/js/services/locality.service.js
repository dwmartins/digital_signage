import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /** Lista localidades por tipo com paginação e filtros. */
    async index(type, page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/localities/${type}`, {
            params: { page, perPage, ...filters },
        });

        return response.data;
    },

    /** Retorna as opções hierárquicas de estados, cidades e bairros. */
    async options(filters = {}) {
        const params = {
            ...filters,
            include_inactive: typeof filters.include_inactive === 'boolean'
                ? Number(filters.include_inactive)
                : filters.include_inactive,
        };

        const response = await axios.get(`${API_URL}/localities/options`, {
            params,
        });

        return response.data;
    },

    /** Cria uma localidade do tipo informado. */
    async create(type, data) {
        const response = await axios.post(`${API_URL}/localities/${type}`, data);

        return response.data;
    },

    /** Atualiza uma localidade. */
    async update(type, data) {
        const response = await axios.put(`${API_URL}/localities/${type}/${data.id}`, data);

        return response.data;
    },

    /** Exclui uma localidade sem vínculos. */
    async destroy(type, id) {
        const response = await axios.delete(`${API_URL}/localities/${type}/${id}`);

        return response.data;
    },
};
