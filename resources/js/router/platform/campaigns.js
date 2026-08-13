const campaignsView = () =>
    import("@/views/platform/campaign/CampaignsView.vue");

export default [
    {
        path: "campanhas",
        name: "platform.campaigns",
        component: campaignsView,
        meta: { title: "Campanhas", permission: "campaigns.view" },
    },
];
