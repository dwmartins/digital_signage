import { API_URL } from '@/helpers/constants';
import axios from 'axios';

const toFormData = (data, update = false) => {
    const payload = new FormData();

    if (update) payload.append('_method', 'PUT');

    Object.entries(data).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            payload.append(key, value);
        }
    });

    return payload;
};

export default {
    /** Lista as mídias com paginação e filtros. */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/media-assets`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },

    /** Retorna os anunciantes disponíveis. */
    async customerOptions() {
        const response = await axios.get(`${API_URL}/media-assets/customer-options`);
        return response.data;
    },

    /** Envia uma nova mídia. */
    async create(data) {
        const response = await axios.post(`${API_URL}/media-assets`, toFormData(data));
        return response.data;
    },

    /** Atualiza os dados ou substitui o arquivo da mídia. */
    async update(data) {
        const response = await axios.post(`${API_URL}/media-assets/${data.id}`, toFormData(data, true));
        return response.data;
    },

    /** Atualiza a decisão da análise interna da mídia. */
    async updateApproval(id, data) {
        const response = await axios.patch(`${API_URL}/media-assets/${id}/approval`, data);
        return response.data;
    },

    /** Exclui a mídia e seu arquivo. */
    async destroy(id) {
        const response = await axios.delete(`${API_URL}/media-assets/${id}`);
        return response.data;
    },
};
