const campaignOnboardingView = () =>
    import("@/views/customer/CampaignOnboardingView.vue");

export default [
    {
        path: "comecar",
        name: "customer.campaign-onboarding",
        component: campaignOnboardingView,
        meta: { title: "Começar campanha" },
    },
];
