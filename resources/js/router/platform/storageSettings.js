const StorageSettingsView = () =>
    import("@/views/platform/setting/StorageSettingsView.vue");

export default [
    {
        path: "configuracoes/armazenamento",
        name: "platform.storage-settings",
        component: StorageSettingsView,
        meta: {
            title: "Armazenamento de arquivos",
            permission: "storage-settings.view",
        },
    },
];
