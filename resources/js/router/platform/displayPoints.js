const view = () => import('@/views/platform/display-point/DisplayPointsView.vue');
export default [{ path: 'pontos-de-exibicao', name: 'platform.display-points', component: view, meta: { title: 'Pontos de exibição', permission: 'display-points.view' } }];
