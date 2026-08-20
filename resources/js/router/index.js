import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/',
        name: 'landing',
        component: () => import('../pages/LandingPage.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
    },
    {
        path: '/app',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: { name: 'panel' } },
            {
                path: 'panel',
                name: 'panel',
                component: () => import('../pages/PanelPage.vue'),
            },
            {
                path: 'menus',
                name: 'menus',
                component: () => import('../pages/MenuEditorPage.vue'),
            },
            {
                path: 'platos',
                name: 'platos',
                component: () => import('../pages/DishesPage.vue'),
            },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (to.name === 'login' && auth.isAuthenticated) {
        return { name: 'panel' };
    }
});

export default router;
