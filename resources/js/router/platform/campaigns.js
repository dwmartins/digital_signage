const campaignsView = () =>
    import("@/views/platform/campaign/CampaignsView.vue");
const campaignFormView = () =>
    import("@/views/platform/campaign/CampaignFormView.vue");

export default [
    {
        path: "campanhas",
        name: "platform.campaigns",
        component: campaignsView,
        meta: { title: "Campanhas", permission: "campaigns.view" },
    },
    {
        path: "campanhas/nova",
        name: "platform.campaigns.create",
        component: campaignFormView,
        meta: { title: "Nova campanha", permission: "campaigns.create" },
    },
    {
        path: "campanhas/:id/editar",
        name: "platform.campaigns.edit",
        component: campaignFormView,
        meta: { title: "Editar campanha", permission: "campaigns.update" },
    },
];
