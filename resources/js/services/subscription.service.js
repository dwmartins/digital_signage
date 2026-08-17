import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Lista assinaturas de campanhas. */
    async index(page = 1, perPage = 7, filters = {}) {
        return (
            await axios.get(`${API_URL}/campaign-subscriptions`, {
                params: { page, perPage, ...filters },
            })
        ).data;
    },
    /** Retorna opções dos formulários e filtros. */
    async options() {
        return (await axios.get(`${API_URL}/campaign-subscriptions/options`))
            .data;
    },
    /** Cria uma assinatura antes do cadastro da campanha. */
    async create(data) {
        return (await axios.post(`${API_URL}/campaign-subscriptions`, data)).data;
    },
    /** Atualiza plano, status e vigência da assinatura. */
    async update(data) {
        return (
            await axios.put(
                `${API_URL}/campaign-subscriptions/${data.id}`,
                data,
            )
        ).data;
    },
    /** Aprova e gera a cobrança inicial. */
    async approve(id, data = {}) {
        return (
            await axios.patch(`${API_URL}/campaign-subscriptions/${id}/approve`, data)
        ).data;
    },
    /** Renova a vigência e gera uma nova cobrança paga. */
    async renew(id, data = {}) {
        return (
            await axios.patch(`${API_URL}/campaign-subscriptions/${id}/renew`, data)
        ).data;
    },
    /** Cancela uma assinatura. */
    async cancel(id) {
        return (
            await axios.patch(`${API_URL}/campaign-subscriptions/${id}/cancel`)
        ).data;
    },
};
