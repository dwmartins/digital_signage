import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Retorna a configuração SMTP sem expor a senha. */
    async show() {
        return (await axios.get(`${API_URL}/settings/email`)).data;
    },

    /** Atualiza e aplica os dados utilizados para envio. */
    async update(data) {
        return (await axios.put(`${API_URL}/settings/email`, data)).data;
    },
};
