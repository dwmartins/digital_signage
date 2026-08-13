import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Lista campanhas com paginação e filtros. */
    async index(page = 1, perPage = 7, filters = {}) {
        return (
            await axios.get(`${API_URL}/campaigns`, {
                params: { page, perPage, ...filters },
            })
        ).data;
    },
    /** Retorna clientes, categorias, planos e mídias disponíveis. */
    async options() {
        return (await axios.get(`${API_URL}/campaigns/options`)).data;
    },
    /** Cria a campanha e sua assinatura pendente. */
    async create(data) {
        return (await axios.post(`${API_URL}/campaigns`, data)).data;
    },
    /** Atualiza os dados e vínculos da campanha. */
    async update(data) {
        return (await axios.put(`${API_URL}/campaigns/${data.id}`, data)).data;
    },
    /** Exclui uma campanha sem histórico financeiro. */
    async destroy(id) {
        return (await axios.delete(`${API_URL}/campaigns/${id}`)).data;
    },
};
