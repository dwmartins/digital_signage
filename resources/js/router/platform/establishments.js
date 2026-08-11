const establishmentsView = () => import('@/views/platform/establishment/EstablishmentsView.vue');

export default [
    {
        path: 'estabelecimentos',
        name: 'platform.establishments',
        component: establishmentsView,
        meta: {
            title: 'Estabelecimentos',
            permission: 'establishments.view'
        }
    }
]
