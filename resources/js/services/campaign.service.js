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
    /** Retorna categorias e assinaturas ainda sem campanha. */
    async options() {
        return (await axios.get(`${API_URL}/campaigns/options`)).data;
    },
    /** Retorna mídias compatíveis com a assinatura e o anunciante. */
    async mediaOptions(subscriptionId) {
        return (await axios.get(`${API_URL}/campaigns/media-options`, {
            params: { subscription_id: subscriptionId },
        })).data;
    },
    /** Pesquisa pontos de exibição com filtros e paginação. */
    async displayPointOptions(page = 1, perPage = 5, filters = {}) {
        return (await axios.get(`${API_URL}/campaigns/display-point-options`, {
            params: { page, perPage, ...filters },
        })).data;
    },
    /** Retorna uma campanha para edição em página própria. */
    async show(id) {
        return (await axios.get(`${API_URL}/campaigns/${id}`)).data;
    },
    /** Cria a campanha e envia sua mídia. */
    async create(data) {
        return (await axios.post(`${API_URL}/campaigns`, this.toFormData(data))).data;
    },
    /** Atualiza os dados e vínculos da campanha. */
    async update(data) {
        const payload = this.toFormData(data);
        payload.append("_method", "PUT");
        return (await axios.post(`${API_URL}/campaigns/${data.id}`, payload)).data;
    },
    /** Exclui uma campanha sem histórico financeiro. */
    async destroy(id) {
        return (await axios.delete(`${API_URL}/campaigns/${id}`)).data;
    },
    /** Desvincula uma mídia sem excluí-la da Biblioteca. */
    async detachMedia(campaignId, mediaId) {
        return (await axios.delete(`${API_URL}/campaigns/${campaignId}/media/${mediaId}`)).data;
    },
    toFormData(data) {
        const payload = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            if (["category_ids", "display_point_ids"].includes(key)) {
                value.forEach((id) => payload.append(`${key}[]`, id));
            } else if (value !== null && value !== undefined) payload.append(key, value);
        });
        return payload;
    },
};
