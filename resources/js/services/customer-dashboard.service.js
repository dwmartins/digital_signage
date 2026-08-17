import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Retorna o estado inicial da dashboard do anunciante. */
    async index() {
        return (await axios.get(`${API_URL}/customer/dashboard`)).data;
    },
};
