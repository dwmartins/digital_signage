const view = () => import('@/views/platform/player/PlayersView.vue');
export default [{ path: 'players', name: 'platform.players', component: view, meta: { title: 'Players (PC)', permission: 'players.view' } }];
