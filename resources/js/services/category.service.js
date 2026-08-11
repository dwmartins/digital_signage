import { API_URL } from '@/helpers/constants';
import axios from 'axios';

export default {
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/categories`, {
            params: { page, perPage, ...filters },
        });
        return response.data;
    },

    async create(data) {
        const response = await axios.post(`${API_URL}/categories`, data);
        return response.data;
    },

    async update(data) {
        const response = await axios.put(`${API_URL}/categories/${data.id}`, data);
        return response.data;
    },

    async destroy(id) {
        const response = await axios.delete(`${API_URL}/categories/${id}`);
        return response.data;
    },
};
