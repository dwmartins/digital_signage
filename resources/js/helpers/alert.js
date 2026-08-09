import { Notyf } from "notyf";

const notyf = new Notyf({
    duration: 4000,
    position: { x: "right", y: "top" },
    types: [
        {
            type: "warning",
            className: "notyf-warning",
            icon: {
                className: "fa-solid fa-triangle-exclamation",
                tagName: "i",
                color: "currentColor",
            },
        },
    ],
});

/**
 * Normaliza mensagens de erro ou string para exibição.
 * @param {*} input - Objeto de erro, string ou undefined
 * @returns {string} - Mensagem pronta para exibir
 */
function normalizeMessage(input) {
    if (!input) return "Ocorreu um erro inesperado.";

    if (input.errors && typeof input.errors === "object") {
        return Object.values(input.errors)
            .flat()
            .map(msg => `• ${msg}`)
            .join("<br>");
    }

    if (input.message) return input.message;

    if (typeof input === "string") return input;

    return "Ocorreu um erro inesperado.";
}

/**
 * Exibe alerta na tela usando Notyf.
 * @param {"success"|"error"|"warning"} type - Tipo de alerta
 * @param {*} message - Mensagem ou objeto de erro
 * @param {number} [duration] - Tempo em ms para exibir
 */
export function showAlert(type = "error", message, duration) {
    const finalMessage = normalizeMessage(message);

    const config = {
        message: finalMessage,
        ...(duration && { duration })
    };

    if (type === "success") {
        return notyf.success(config);
    }

    if (type === "warning") {
        return notyf.open({
            type: "warning",
            ...config,
        });
    }

    return notyf.error(config);
}
