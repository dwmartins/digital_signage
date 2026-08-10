import { useRoute, useRouter } from 'vue-router';
import { parseDate, parseDateTime, safeISOToDate, safeISOToDateTime } from '../helpers/date';

export function useQueryFilters(filters, currentPage) {
    const route = useRoute();
    const router = useRouter();

    /**
     * Aplica filtros vindos da URL
     */
    const applyFromRoute = () => {
        const { page, ...queryFilters } = route.query;

        for (const key in queryFilters) {
            if (!filters[key]) continue;

            const { type } = filters[key];
            let value = queryFilters[key];

            if (type === 'date') {
                filters[key].value = parseDate(value);
                continue;
            }

            if (type === 'datetime') {
                filters[key].value = parseDateTime(value);
                continue;
            }

            if (type === 'boolean' || type === 'number') {
                filters[key].value = Number(value);
                continue;
            }

            filters[key].value = value;
        }

        currentPage.value = page ? Number(page) : 1;
    };

    /**
     * Sincroniza filtros com a URL
     */
    const syncToRoute = (page = 1) => {
        const query = {};

        if (page !== 1) query.page = page;

        for (const key in filters) {
            const { value, type } = filters[key];

            if (value == null || value === '') continue;

            if (type === 'date') {
                query[key] = safeISOToDate(value);
                continue;
            }

            if (type === 'datetime') {
                query[key] = safeISOToDateTime(value);
                continue;
            }

            query[key] = value;
        }

        router.replace({ query });
    };

    /**
     * Retorna filtros prontos para API
     */
    const buildApiFilters = () => {
        const cloneFilters = {};

        for (const key in filters) {
            const { value, type } = filters[key];

            if (value == null || value === '') continue;

            if (type === 'date') {
                cloneFilters[key] = safeISOToDate(value);
                continue;
            }

            if (type === 'boolean' || type === 'number') {
                cloneFilters[key] = Number(value);
                continue;
            }

            cloneFilters[key] = value;
        }

        return cloneFilters;
    };

    return {
        applyFromRoute,
        syncToRoute,
        buildApiFilters
    };
}
