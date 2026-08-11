import { APP_NAME } from "@/helpers/constants";
import authService from "@/services/auth.service";
import { useAuthStore } from "@/stores/authStore";
import { pageLoadingStore } from "@/stores/pageLoadingStore";
import { createRouter, createWebHistory } from "vue-router";
import platformRoutes from './platform';
import customerRoutes from './customer';

const loginView = () => import("@/views/auth/LoginView.vue");
const NotFoundView = () => import('@/views/NotFoundView.vue'); 

const routes = [
    {
        path: '/',
        redirect: '/entrar'
    },
    {
        path: '/entrar',
        name: 'login',
        component: loginView
    },
    ...platformRoutes,
    ...customerRoutes,
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found-view',
        component: NotFoundView
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    }
});

let sessionValidationPromise = null;
const retriedLazyRoutes = new Set();

router.beforeEach(async (to) => {
    document.title = to.meta.title || APP_NAME;

    const auth = useAuthStore();

    if(to.name === 'login') {
        if(auth.isAuthenticate() || await validateSession()) {
            return redirectToDashboard();
        }

        return true;
    }

    if(to.meta.requiresAuth) {
        if(!auth.isAuthenticate() && !(await validateSession())) {
            return {
                name: 'login',
                query: {
                    redirect: to.fullPath,
                },
            }
        }
    }

    if(!canAccessRoute(to, auth)) {
        return redirectToDashboard();
    }

    return true;
});

router.onError((error, to) => {
    if(!isLazyLoadError(error) || retriedLazyRoutes.has(to.fullPath)) return;

    retriedLazyRoutes.add(to.fullPath);
    router.replace(to.fullPath);
});

router.afterEach((to, from, failure) => {
    if(!failure) retriedLazyRoutes.delete(to.fullPath);
});

async function validateSession() {
    if(sessionValidationPromise) return sessionValidationPromise;

    sessionValidationPromise = (async () => {
        pageLoadingStore.show();

        try {
            const { is_valid } = await authService.validate();
            return is_valid;
        } finally {
            pageLoadingStore.hide();
            sessionValidationPromise = null;
        }
    })();

    return sessionValidationPromise;
}

function canAccessRoute(to, auth) {
    if(to.meta.requiresPlatformUser && !auth.isPlatformUser()) {
        return false;
    }

    if(to.meta.requiresCustomerUser && !auth.isCustomer()) {
        return false;
    }

    return !to.meta.permission || auth.hasPermission(to.meta.permission);
}

function isLazyLoadError(error) {
    const message = String(error?.message || error).toLowerCase();

    return message.includes('dynamically imported module')
        || message.includes('module script failed')
        || message.includes('error loading dynamically imported module')
        || message.includes('unable to preload css');
}

function redirectToDashboard() {
    return authService.dashboardRoute();
}

export default router;
