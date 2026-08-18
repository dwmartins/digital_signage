import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Lista as campanhas pertencentes ao anunciante autenticado. */
    async index(page = 1, perPage = 6, filters = {}) {
        return (
            await axios.get(`${API_URL}/customer/campaigns`, {
                params: { page, perPage, ...filters },
            })
        ).data;
    },

    /** Retorna as opções disponíveis para os filtros. */
    async options() {
        return (await axios.get(`${API_URL}/customer/campaigns/options`)).data;
    },

    /** Retorna todos os detalhes de uma campanha do anunciante. */
    async show(id) {
        return (await axios.get(`${API_URL}/customer/campaigns/${id}`)).data;
    },

    /** Ativa ou pausa uma campanha. */
    async updateStatus(id, status) {
        return (
            await axios.patch(`${API_URL}/customer/campaigns/${id}/status`, {
                status,
            })
        ).data;
    },
};
