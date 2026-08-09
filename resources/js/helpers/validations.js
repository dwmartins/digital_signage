import { showAlert } from "./alert";

/**
 * Valida os campos obrigatórios em um formulário e atualiza os campos com erros.
 *
 * @param {Object} formData - Formulário com dados contendo valores de campo.
 * @param {Array<{id: string, label: string}>} requiredFields - Campos que devem ser preenchidos.
 * @param {Object} fieldErrorsRef - Objeto para armazenar mensagens de erro.
 *
 * @returns {boolean} Retorna Verdadeiro se todos os campos obrigatórios estiverem preenchidos, falso caso contrário.
 */
export function validateForm(formData, requiredFields, fieldErrorsRef) {
    let isValid = true;
    const new_erros = {};

    for (const {id, label, empty_message} of requiredFields) {
        const value = formData[id];

        if (value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length)) {
            isValid = false;
            new_erros[id] = empty_message ? empty_message : [`O campo "${label}" é obrigatório.`];
        }
    }

    Object.assign(fieldErrorsRef, new_erros);

    if(!isValid) {
        const filteredErrors = Object.entries(fieldErrorsRef).reduce((acc, [key, value]) => {
            if (value !== null) acc[key] = value;
            return acc;
        }, {});

        showAlert('error', {
            errors: filteredErrors
        }, 4000);
    }

    return isValid;
}
