export const platformNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/platform/painel' },
    { label: 'Clientes anunciantes', icon: 'pi pi-users', to: '/platform/clientes', permission: 'customers.view' },
    {
        label: 'Campanha',
        icon: 'pi pi-megaphone',
        children: [
            { label: 'Categorias', icon: 'pi pi-tags', to: '/platform/categorias', permission: 'categories.view' },
        ],
    },
    {
        label: 'Rede',
        icon: 'pi pi-sitemap',
        children: [
            { label: 'Estabelecimentos', icon: 'pi pi-building', to: '/platform/estabelecimentos', permission: 'establishments.view' },
            { label: 'Pontos de exibição', icon: 'pi pi-map-marker', to: '/platform/pontos-de-exibicao', permission: 'display-points.view' },
            { label: 'Telas', icon: 'pi pi-desktop', to: '/platform/telas', permission: 'screens.view' },
            { label: 'Players (PC)', icon: 'pi pi-server', to: '/platform/players', permission: 'players.view' },
        ],
    },
    { label: 'Usuários suporte', icon: 'pi pi-headphones', to: '/platform/usuarios-suporte', permission: 'support-users.view' },
];

export const customerNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/painel/inicio' },
];
