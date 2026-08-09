import DefaultLayout from '@/layouts/DefaultLayout.vue';

const dashboardView = () => import('@/views/platform/DashboardView.vue');

export default [
    {
        path: '/platform',
        component: DefaultLayout,
        props: { area: 'platform' },
        meta: {requiresAuth: true, requiresPlatformUser: true},
        children: [
            {
                path: '',
                redirect: { name: 'platform.dashboard' },
            },
            {
                path: 'painel',
                name: 'platform.dashboard',
                component: dashboardView,
                meta: { title: 'Dashboard da plataforma' },
            },
        ]
    }
];
