import { reactive } from "vue";

/**
 * Armazenamento reativo para controlar o estado de carregamento da página.
 */
export const pageLoadingStore = reactive({
    message: '',
    isLoading: false,

    /**
     * Exibe a tela de carregamento com uma mensagem opcional.
     * @param {string} message - Mensagem a ser exibida.
     */
    show(message = '') {
        this.message = message;
        this.isLoading = true;
    },

    /**
     * Oculta a tela de carregamento e limpa a mensagem.
     */
    hide() {
        this.isLoading = false;
        this.message = '';
    }
});