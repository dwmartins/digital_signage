import { API_URL } from "@/helpers/constants";

export default {
    /**
     * Lista os usuários suporte com paginação e filtros opcionais.
     *
     * @param {Number} page - Página atual (padrão: 1)
     * @param {Number} perPage - Quantidade de registros por página (padrão: 7)
     * @param {Object} filters - Filtros adicionais enviados como query params
     *
     * @returns {Promise<{data: Object[], pagination: {
     *      current_page: Number,
     *      last_page: Number,
     *      per_page: Number,
     *      total: Number
     * }}>}
     * Retorna um objeto contendo:
     * - data: Lista de usuários suporte
     * - pagination: Dados da paginação
     */
    async index(page = 1, perPage = 7, filters = {}) {
        const response = await axios.get(`${API_URL}/support-users`, {
            params: {
                page,
                perPage,
                ...filters,
            },
        });

        return response.data;
    },
}