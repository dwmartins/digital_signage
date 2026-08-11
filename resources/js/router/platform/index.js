import DefaultLayout from '@/layouts/DefaultLayout.vue';
import supportUsers from './supportUsers';
import customerUsers from './customerUsers';
import categories from './categories';

const dashboardView = () => import('@/views/platform/DashboardView.vue');
const profileView = () => import('@/views/ProfileView.vue');

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
            {
                path: 'perfil',
                name: 'platform.profile',
                component: profileView
            },

            ...supportUsers,
            ...customerUsers,
            ...categories
        ]
    }
];
