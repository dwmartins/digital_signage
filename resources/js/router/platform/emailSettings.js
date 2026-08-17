const EmailSettingsView = () =>
    import("@/views/platform/setting/EmailSettingsView.vue");

export default [
    {
        path: "configuracoes/email",
        name: "platform.email-settings",
        component: EmailSettingsView,
        meta: {
            title: "Configuração de e-mail",
            permission: "email-settings.view",
        },
    },
];
