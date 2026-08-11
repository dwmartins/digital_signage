const supportUsersView = () => import('@/views/platform/support-user/SupportUsersView.vue');
const supportUserPermissionsView = () => import('@/views/platform/support-user/SupportUserPermissionsView.vue');

export default [
    {
        path: 'usuarios-suporte',
        name: 'platform.support-users',
        component: supportUsersView,
        meta: {
            title: 'Usuários suporte',
            permission: 'support-users.view'
        }
    },
    {
        path: 'usuarios-suporte/:id/permissoes',
        name: 'platform.support-users.permissions',
        component: supportUserPermissionsView,
        meta: {
            title: 'Permissões do usuário suporte',
            permission: 'support-users.permissions.update'
        }
    }
]
