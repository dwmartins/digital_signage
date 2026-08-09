/**
 * Converte uma data para o formato ISO (AAAA-MM-DD).
 *
 * @param {string|Date|null|undefined} date
 *
 * @returns {string|null}
 *  Data ISO (AAAA-MM-DD) ou nulo se a data for inválida.
 */
export function safeISOToDate(date) {
    if (!date) return null;

    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date)) {
        return date.slice(0, 10);
    }

    const d = new Date(date);
    if (isNaN(d.getTime())) return null;

    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/**
 * Converte um datetime para o formato ISO (AAAA-MM-DD HH:mm:ss).
 *
 * @param {string|Date|null|undefined} datetime
 *
 * @returns {string|null}
 *  DateTime ISO (AAAA-MM-DD HH:mm:ss) ou nulo se o datetime for inválido.
 */
export function safeISOToDateTime(datetime) {
    const d = new Date(datetime);
    if (isNaN(d.getTime())) return null;
    return d.toISOString().slice(0, 19).replace('T', ' ');
}

/**
 * Formata a data para JavaScript
 *
 * @param {string|Date|null|undefined} date
 *
 * @returns {Date|null}
 */
export function parseDate(date) {
    if (date instanceof Date) {
        return date;
    }

    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day);
}

/**
 * Formata o datetime para JavaScript
 *
 * @param {string|Date|null|undefined} datetime
 *
 * @returns {Date|null}
 */
export function parseDateTime(datetime) {
    if (datetime instanceof Date) {
        return datetime;
    }

    const dt = datetime.replace(' ', 'T');
    const d = new Date(dt);

    return isNaN(d.getTime()) ? null : d;
}

/**
 * Formata uma data (YYYY-MM-DD) para o formato brasileiro (dd/mm/aaaa).
 *
 * @param {string|null|undefined} date
 * @returns {string}
 */
export function formatDate(date) {
    if (!date) return '-';

    const isoDate = safeISOToDate(date);
    if (!isoDate) return '-';

    const [year, month, day] = isoDate.split('-').map(Number);

    return new Intl.DateTimeFormat('pt-BR').format(
        new Date(year, month - 1, day)
    );
}

/**
 * Formata um datetime (YYYY-MM-DD HH:mm:ss) para o formato brasileiro (dd/mm/aaaa HH:mm:ss).
 *
 * @param {string|null|undefined} datetime
 * @returns {string}
 */
export function formatDateTime(datetime) {
    if (!datetime) return '-';

    const d = parseDateTime(datetime);
    if (!d) return '-';

    return new Intl.DateTimeFormat('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(d);
}

/**
 * Verifica se uma data está no passado.
 *
 * @param {string|Date|null|undefined} date
 *
 * @returns {boolean}
 *  Retorna true se a data for anterior à data atual.
 */
export function isDateInPast(date) {
    if (!date) return false;

    const d = date instanceof Date ? date : parseDate(date);
    if (!d || isNaN(d.getTime())) return false;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    d.setHours(0, 0, 0, 0);

    return d < today;
}

/**
 * Verifica se um datetime está no passado.
 *
 * @param {string|Date|null|undefined} datetime
 *
 * @returns {boolean}
 *  Retorna true se o datetime for anterior ao momento atual.
 */
export function isDateTimeInPast(datetime) {
    if (!datetime) return false;

    const d = datetime instanceof Date ? datetime : parseDateTime(datetime);
    if (!d || isNaN(d.getTime())) return false;

    return d.getTime() < Date.now();
}
