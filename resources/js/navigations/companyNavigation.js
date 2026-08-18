export const platformNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/platform/painel' },
    { label: 'Clientes anunciantes', icon: 'pi pi-users', to: '/platform/clientes', permission: 'customers.view' },
    {
        label: 'Campanha',
        icon: 'pi pi-megaphone',
        children: [
            { label: 'Campanhas', icon: 'pi pi-megaphone', to: '/platform/campanhas', permission: 'campaigns.view' },
            { label: 'Categorias', icon: 'pi pi-tags', to: '/platform/categorias', permission: 'categories.view' },
            { label: 'Biblioteca de mídias', icon: 'pi pi-images', to: '/platform/midias', permission: 'media.view' },
        ],
    },
    {
        label: 'Comercial',
        icon: 'pi pi-wallet',
        children: [
            { label: 'Planos', icon: 'pi pi-box', to: '/platform/planos', permission: 'plans.view' },
            { label: 'Assinaturas', icon: 'pi pi-file-check', to: '/platform/assinaturas', permission: 'subscriptions.view' },
            { label: 'Transações', icon: 'pi pi-receipt', to: '/platform/transacoes', permission: 'transactions.view' },
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
    {
        label: 'Configurações',
        icon: 'pi pi-cog',
        children: [
            { label: 'Localidades', icon: 'pi pi-map', to: '/platform/configuracoes/localidades', permission: 'localities.view' },
            { label: 'Envio de e-mail', icon: 'pi pi-envelope', to: '/platform/configuracoes/email', permission: 'email-settings.view' },
            { label: 'Armazenamento', icon: 'pi pi-database', to: '/platform/configuracoes/armazenamento', permission: 'storage-settings.view' },
        ],
    },
    { label: 'Usuários suporte', icon: 'pi pi-headphones', to: '/platform/usuarios-suporte', permission: 'support-users.view' },
];

export const customerNavItems = [
    { label: 'Dashboard', icon: 'pi pi-chart-line', to: '/painel/inicio' },
    { label: 'Minhas assinaturas', icon: 'pi pi-file-check', to: '/painel/assinaturas' },
];
