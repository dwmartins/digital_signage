import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Lista os planos comerciais. */
    async index() {
        return (await axios.get(`${API_URL}/plans`)).data;
    },
    /** Cadastra um plano. */
    async create(data) {
        return (await axios.post(`${API_URL}/plans`, data)).data;
    },
    /** Atualiza um plano. */
    async update(data) {
        return (await axios.put(`${API_URL}/plans/${data.id}`, data)).data;
    },
    /** Exclui um plano sem assinaturas. */
    async destroy(id) {
        return (await axios.delete(`${API_URL}/plans/${id}`)).data;
    },
};
