import DefaultLayout from '@/layouts/DefaultLayout.vue';

const dashboardView = () => import('@/views/customer/DashboardView.vue');
const campaignOnboardingView = () => import('@/views/customer/CampaignOnboardingView.vue');
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
            {
                path: 'comecar',
                name: 'customer.campaign-onboarding',
                component: campaignOnboardingView,
                meta: { title: 'Começar campanha' },
            },
        ],
    },
];
