const categoriesView = () => import('@/views/platform/category/CategoriesView.vue');

export default [
    {
        path: 'categorias',
        name: 'platform.categories',
        component: categoriesView,
        meta: {
            title: 'Categorias',
            permission: 'categories.view'
        }
    }
]
