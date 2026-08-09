export const APP_NAME = import.meta.env.VITE_APP_NAME;

export const API_URL = '/api';

export const emptyImg = new URL('@assets/svg/empty.svg', import.meta.url).href;
export const logo     = new URL('@assets/img/logo.png', import.meta.url).href;

export const DEFAULT_TIMEZONE = 'America/Sao_Paulo';

export const ROLE_ADMIN    = 'admin';
export const ROLE_SUPPORT  = 'support';
export const ROLE_CUSTOMER = 'customer';