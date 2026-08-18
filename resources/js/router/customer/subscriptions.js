const subscriptionsView = () =>
    import("@/views/customer/SubscriptionsView.vue");

export default [
    {
        path: "assinaturas",
        name: "customer.subscriptions",
        component: subscriptionsView,
        meta: { title: "Minhas assinaturas" },
    },
];
