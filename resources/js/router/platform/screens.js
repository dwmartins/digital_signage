const screensView = () => import('@/views/platform/screen/ScreensView.vue');

export default [
    {
        path: 'telas',
        name: 'platform.screens',
        component: screensView,
        meta: {
            title: 'Telas',
            permission: 'screens.view'
        }
    }
]
