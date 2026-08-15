const LocalitiesView = () => import('@/views/platform/locality/LocalitiesView.vue');

export default [
    {
        path: 'configuracoes/localidades',
        name: 'platform.localities',
        component: LocalitiesView,
        meta: {
            title: 'Localidades',
            permission: 'localities.view',
        },
    },
];
