const supportUsersView = () => import('@/views/platform/support-user/SupportUsersView.vue');

export default [
    {
        path: 'usuarios-suporte',
        name: 'platform.support-users',
        component: supportUsersView,
        meta: {
            title: 'Usuários suporte',
            permission: 'support-users.view'
        }
    }
]