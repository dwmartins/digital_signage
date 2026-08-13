import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /** Lista os players com paginação e filtros. */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/players`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },

    /** Retorna estabelecimentos e pontos disponíveis nos filtros. */
    async filterOptions() {
        const response = await axios.get(`${API_URL}/players/filter-options`);
        return response.data;
    },

    /** Cria um player. */
    async create(data) {
        const response = await axios.post(`${API_URL}/players`, data);
        return response.data;
    },

    /** Atualiza um player. */
    async update(data) {
        const response = await axios.put(`${API_URL}/players/${data.id}`, data);
        return response.data;
    },

    /** Exclui um player. */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/players/${id}`);
        return response.data;
    },
};
