const customerUsersView = () => import('@/views/platform/customer-user/CustomerUsersView.vue');

export default [
    {
        path: 'clientes',
        name: 'platform.customer-users',
        component: customerUsersView,
        meta: {
            title: 'Clientes anunciantes',
            permission: 'customers.view'
        }
    }
]
