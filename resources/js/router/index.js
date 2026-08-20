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
            {
                path: '',
                redirect: (to) => {
                    const auth = useAuthStore();
                    return auth.isSuperAdmin ? { name: 'superadmin-tenants' } : { name: 'panel' };
                },
            },
            // Rutas Operativas de Cocina / Tenant
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
            // Rutas Super Admin (Gestión de Plataforma SaaS)
            {
                path: 'admin/tenants',
                name: 'superadmin-tenants',
                component: () => import('../pages/superadmin/TenantsAdminPage.vue'),
            },
            // Detalle / drill-down de una concesionaria específica
            {
                path: 'admin/tenants/:tenantId',
                component: () => import('../pages/superadmin/tenant/TenantDetailShell.vue'),
                props: true,
                children: [
                    {
                        path: '',
                        name: 'superadmin-tenant-overview',
                        component: () => import('../pages/superadmin/tenant/TenantOverviewPage.vue'),
                    },
                    {
                        path: 'empresas',
                        name: 'superadmin-tenant-companies',
                        component: () => import('../pages/superadmin/tenant/TenantCompaniesPage.vue'),
                    },
                    {
                        path: 'sucursales',
                        name: 'superadmin-tenant-branches',
                        component: () => import('../pages/superadmin/tenant/TenantBranchesPage.vue'),
                    },
                    {
                        path: 'usuarios',
                        name: 'superadmin-tenant-users',
                        component: () => import('../pages/superadmin/tenant/TenantUsersPage.vue'),
                    },
                    {
                        path: 'reportes',
                        name: 'superadmin-tenant-reports',
                        component: () => import('../pages/superadmin/tenant/TenantReportsPage.vue'),
                    },
                    {
                        path: 'editar',
                        name: 'superadmin-tenant-edit',
                        component: () => import('../pages/superadmin/tenant/TenantEditPage.vue'),
                    },
                ],
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
        if (auth.isSuperAdmin) {
            return { name: 'superadmin-tenants' };
        }
        return { name: 'panel' };
    }
    if (auth.isAuthenticated && auth.isSuperAdmin && to.name === 'panel') {
        return { name: 'superadmin-tenants' };
    }
});

export default router;
