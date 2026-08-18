import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Lista as assinaturas pertencentes ao anunciante autenticado. */
    async index(page = 1, perPage = 6, filters = {}) {
        return (
            await axios.get(`${API_URL}/customer/subscriptions`, {
                params: { page, perPage, ...filters },
            })
        ).data;
    },
};
