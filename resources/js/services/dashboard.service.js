import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Retorna os indicadores comerciais e operacionais da dashboard. */
    async index() {
        return (await axios.get(`${API_URL}/platform/dashboard`)).data;
    },
};
