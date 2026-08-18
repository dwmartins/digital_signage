const campaignOnboardingView = () =>
    import("@/views/customer/CampaignOnboardingView.vue");
const campaignsView = () => import("@/views/customer/CampaignsView.vue");
const campaignDetailsView = () =>
    import("@/views/customer/CampaignDetailsView.vue");

export default [
    {
        path: "campanhas",
        name: "customer.campaigns",
        component: campaignsView,
        meta: { title: "Minhas campanhas" },
    },
    {
        path: "campanhas/:id",
        name: "customer.campaigns.show",
        component: campaignDetailsView,
        meta: { title: "Detalhes da campanha" },
    },
    {
        path: "comecar",
        name: "customer.campaign-onboarding",
        component: campaignOnboardingView,
        meta: { title: "Começar campanha" },
    },
];
