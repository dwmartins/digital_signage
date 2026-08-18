import DefaultLayout from '@/layouts/DefaultLayout.vue';
import campaigns from './campaigns';
import subscriptions from './subscriptions';

const dashboardView = () => import('@/views/customer/DashboardView.vue');
const profileView = () => import('@/views/ProfileView.vue');

export default [
    {
        path: '/painel',
        component: DefaultLayout,
        props: { area: 'customer' },
        meta: { requiresAuth: true, requiresCustomerUser: true },
        children: [
            {
                path: '',
                redirect: { name: 'customer.dashboard' },
            },
            {
                path: 'inicio',
                name: 'customer.dashboard',
                component: dashboardView,
                meta: { title: 'Dashboard' },
            },
            {
                path: 'perfil',
                name: 'customer.profile',
                component: profileView
            },
            ...subscriptions,
            ...campaigns,
        ],
    },
];
