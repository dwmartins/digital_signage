export const platformNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/platform/painel' },
    { label: 'Clientes anunciantes', icon: 'pi pi-users', to: '/platform/clientes', permission: 'customers.view' },
    { label: 'Usuários suporte', icon: 'pi pi-headphones', to: '/platform/usuarios-suporte', permission: 'support-users.view' },
];

export const customerNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/painel/inicio' },
];
