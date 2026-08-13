import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    /** Lista os pontos de exibição com paginação e filtros. */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/display-points`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },
    /** Retorna as opções disponíveis para os vínculos. */
    async options(id = null) {
        const response = await axios.get(`${API_URL}/display-points/options`, { params: { id } });
        return response.data;
    },
    /** Cria um ponto de exibição. */
    async create(data) {
        const response = await axios.post(`${API_URL}/display-points`, data);
        return response.data;
    },
    /** Atualiza um ponto de exibição. */
    async update(data) {
        const response = await axios.put(`${API_URL}/display-points/${data.id}`, data);
        return response.data;
    },
    /** Exclui um ponto de exibição. */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/display-points/${id}`);
        return response.data;
    },
};
