import { showAlert } from "./alert";

/**
 * 
 * @param {string} value CNPJ
 * @returns {string} CNPJ formatado ex: 22.345.678/0001-90
 */
export function formatCNPJ(value) {
    if (!value) return '';

    const digits = value.replace(/\D/g, '');

    if (digits.length !== 14) {
        return value;
    }

    return digits.replace(
        /^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/,
        '$1.$2.$3/$4-$5'
    );
}

/**
 *
 * @param {string} value CPF
 * @returns {string} CPF formatado ex: 123.456.789-00
 */
export function formatCPF(value) {
    if (!value) return '';

    const digits = value.replace(/\D/g, '');

    if (digits.length !== 11) {
        return value;
    }

    return digits.replace(
        /^(\d{3})(\d{3})(\d{3})(\d{2})$/,
        '$1.$2.$3-$4'
    );
}

/**
 * 
 * @param {string} itemName Nome do item a ser copiado
 * @param {*} item o item a ser copiado
 */
export async function copyItem(itemName, item){
    try {
        await navigator.clipboard.writeText(item);
        showAlert('success', `${itemName} copiado para a área de transferência`, 1500);
    } catch (err) {
        showAlert('error', 'Não foi possível copiar o item');
    }
}

/**
 * 
 * @param {string} phone 
 * @returns {string} Telefone formatado 
 */
export function formatPhone(phone) {
    if (!phone) return "";

    const digits = phone.replace(/\D/g, "");

    if(digits.length === 11) {
        return digits.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
    }

    if(digits.length === 10) {
        return digits.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
    }

    return digits;
}

/**
 * @param {String} str 
 * @returns {string} retorna a string com primeira letra maiúscula e os resto minuscula.
 */
export function capitalizeFirstLetter(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
