import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Lista o histórico de transações financeiras. */
    async index(page = 1, perPage = 7, filters = {}) {
        return (
            await axios.get(`${API_URL}/transactions`, {
                params: { page, perPage, ...filters },
            })
        ).data;
    },
};
