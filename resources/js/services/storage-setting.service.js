import { API_URL } from "@/helpers/constants";
import axios from "axios";

export default {
    /** Retorna os provedores de armazenamento sem expor chaves secretas. */
    async show() {
        return (await axios.get(`${API_URL}/settings/storage`)).data;
    },

    /** Atualiza o destino padrão dos próximos arquivos enviados. */
    async update(data) {
        return (await axios.put(`${API_URL}/settings/storage`, data)).data;
    },
};
