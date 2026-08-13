const plans = () => import("@/views/platform/plan/PlansView.vue");
const subscriptions = () =>
    import("@/views/platform/subscription/SubscriptionsView.vue");
const transactions = () =>
    import("@/views/platform/transaction/TransactionsView.vue");

export default [
    {
        path: "planos",
        name: "platform.plans",
        component: plans,
        meta: { title: "Planos", permission: "plans.view" },
    },
    {
        path: "assinaturas",
        name: "platform.subscriptions",
        component: subscriptions,
        meta: { title: "Assinaturas", permission: "subscriptions.view" },
    },
    {
        path: "transacoes",
        name: "platform.transactions",
        component: transactions,
        meta: { title: "Transações", permission: "transactions.view" },
    },
];
