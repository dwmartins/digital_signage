export const platformNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/platform/painel' },
    { label: 'Empresas', icon: 'pi pi-building', to: '/platform/empresas', permission: 'companies.view' },
    { label: 'Usuários suporte', icon: 'pi pi-headphones', to: '/platform/usuarios-suporte', permission: 'support-users.view' },
];

export const customerNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/painel/inicio' },
];