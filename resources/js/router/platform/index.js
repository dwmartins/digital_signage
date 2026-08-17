import DefaultLayout from '@/layouts/DefaultLayout.vue';
import supportUsers from './supportUsers';
import customerUsers from './customerUsers';
import categories from './categories';
import establishments from './establishments';
import screens from './screens';
import players from './players';
import displayPoints from './displayPoints';
import media from './media';
import commercial from './commercial';
import campaigns from './campaigns';
import localities from './localities';
import emailSettings from './emailSettings';
import storageSettings from './storageSettings';

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
            ...categories,
            ...establishments,
            ...screens,
            ...players,
            ...displayPoints,
            ...media,
            ...campaigns,
            ...localities,
            ...emailSettings,
            ...storageSettings,
            ...commercial
        ]
    }
];
