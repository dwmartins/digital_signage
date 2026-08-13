const mediaView = () => import('@/views/platform/media/MediaLibraryView.vue');

export default [
    {
        path: 'midias',
        name: 'platform.media',
        component: mediaView,
        meta: {
            title: 'Biblioteca de mídias',
            permission: 'media.view'
        }
    }
]
