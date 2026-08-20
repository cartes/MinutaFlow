<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BrandMark from '../components/BrandMark.vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const isSidebarOpen = ref(true);
const isUserDropdownOpen = ref(false);
const isTenantSearchOpen = ref(false);

const navigationGroups = [
    {
        title: 'Plataforma SaaS',
        items: [
            {
                label: 'Dashboard Global',
                to: '/admin/dashboard',
                icon: 'chart',
                badge: 'Live',
            },
            {
                label: 'Concesionarias (Tenants)',
                to: '/admin/tenants',
                icon: 'building-office',
            },
            {
                label: 'Empresas Clientes',
                to: '/admin/companies',
                icon: 'users-group',
            },
            {
                label: 'Catálogo Global & Recetas',
                to: '/admin/catalog',
                icon: 'book-open',
            },
        ],
    },
    {
        title: 'Operaciones & Control',
        items: [
            {
                label: 'Suscripciones y Cobros',
                to: '/admin/subscriptions',
                icon: 'credit-card',
            },
            {
                label: 'Auditoría & Trazabilidad',
                to: '/admin/audit-logs',
                icon: 'clipboard-document-list',
            },
            {
                label: 'Salud del Sistema & APIs',
                to: '/admin/health',
                icon: 'server-stack',
                status: 'ok',
            },
        ],
    },
    {
        title: 'Administración',
        items: [
            {
                label: 'Usuarios y Permisos',
                to: '/admin/users',
                icon: 'shield-check',
            },
            {
                label: 'Configuración Global',
                to: '/admin/settings',
                icon: 'adjustments-horizontal',
            },
        ],
    },
];

async function logout() {
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>

<template>
    <div class="min-h-screen bg-[#0f1412] text-slate-100 flex flex-col font-sans antialiased">
        <!-- Top bar de alerta modo Super Admin -->
        <header class="h-14 bg-[#141b18] border-b border-white/10 px-4 sm:px-6 flex items-center justify-between z-30 shrink-0">
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 focus:outline-none lg:hidden"
                    @click="isSidebarOpen = !isSidebarOpen"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex items-center gap-3">
                    <router-link to="/admin" class="flex items-center gap-2">
                        <BrandMark size="sm" />
                    </router-link>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-400 border border-emerald-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        SUPER ADMIN GLOBAL
                    </span>
                </div>
            </div>

            <!-- Buscador global & Tenant Switcher -->
            <div class="flex items-center gap-3">
                <div class="relative hidden sm:block">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg bg-white/5 border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:border-white/20 hover:bg-white/10 transition-colors"
                        @click="isTenantSearchOpen = true"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Buscar Concesionaria o Tenant...</span>
                        <kbd class="ml-2 rounded bg-black/40 px-1.5 py-0.5 text-[10px] text-slate-400 border border-white/5">⌘K</kbd>
                    </button>
                </div>

                <router-link
                    to="/app"
                    class="hidden md:inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-emerald-400 transition-colors px-2 py-1"
                >
                    <span>Ir a Vista Operativa</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </router-link>

                <!-- Perfil / Dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2.5 p-1 rounded-lg hover:bg-white/5 transition-colors focus:outline-none"
                        @click="isUserDropdownOpen = !isUserDropdownOpen"
                    >
                        <div class="h-8 w-8 rounded-full bg-emerald-700/80 border border-emerald-500/30 flex items-center justify-center text-xs font-bold text-white">
                            {{ auth.initials || 'SA' }}
                        </div>
                        <div class="hidden sm:block text-left leading-tight">
                            <div class="text-xs font-medium text-slate-200">{{ auth.user?.name || 'Administrador Global' }}</div>
                            <div class="text-[11px] text-slate-400">{{ auth.user?.email || 'admin@minutaflow.com' }}</div>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        v-show="isUserDropdownOpen"
                        class="absolute right-0 mt-2 w-56 rounded-xl bg-[#18211e] border border-white/10 p-1.5 shadow-2xl z-50 text-xs"
                    >
                        <div class="px-3 py-2 border-b border-white/5 mb-1">
                            <p class="font-medium text-slate-200">Plataforma Central</p>
                            <p class="text-[11px] text-slate-400">Rol: Super Administrator</p>
                        </div>
                        <router-link
                            to="/admin/settings"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-white/5 hover:text-white"
                            @click="isUserDropdownOpen = false"
                        >
                            Ajustes de cuenta
                        </router-link>
                        <router-link
                            to="/admin/audit-logs"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-white/5 hover:text-white"
                            @click="isUserDropdownOpen = false"
                        >
                            Registro de auditoría
                        </router-link>
                        <div class="my-1 border-t border-white/5"></div>
                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-rose-400 hover:bg-rose-500/10 transition-colors text-left"
                            @click="logout"
                        >
                            Cerrar sesión global
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Cuerpo del Layout Super Admin -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar -->
            <aside
                class="w-64 bg-[#141b18] border-r border-white/10 flex flex-col justify-between shrink-0 transition-transform duration-200 z-20"
                :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="p-4 space-y-6 overflow-y-auto">
                    <!-- Selector activo de entorno -->
                    <div class="rounded-xl bg-white/5 border border-white/10 p-3">
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Entorno</div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-200">Producción (MinutaFlow Cloud)</span>
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        </div>
                    </div>

                    <!-- Grupos de Navegación -->
                    <nav class="space-y-6">
                        <div v-for="group in navigationGroups" :key="group.title" class="space-y-1">
                            <h3 class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                {{ group.title }}
                            </h3>
                            <div class="space-y-0.5">
                                <router-link
                                    v-for="item in group.items"
                                    :key="item.label"
                                    :to="item.to"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-slate-300 hover:bg-white/5 hover:text-white transition-colors"
                                    active-class="!bg-emerald-500/15 !text-emerald-400 !font-semibold border-l-2 border-emerald-400"
                                >
                                    <span>{{ item.label }}</span>
                                    <span
                                        v-if="item.badge"
                                        class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-400"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </router-link>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- Footer del Sidebar -->
                <div class="p-4 border-t border-white/10 bg-black/20 text-[11px] text-slate-400 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-300">MinutaFlow Core</p>
                        <p>v2.4.0-production</p>
                    </div>
                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 text-[10px]">LatAm</span>
                </div>
            </aside>

            <!-- Contenedor Principal de Contenido -->
            <main class="flex-1 bg-[#101714] overflow-y-auto p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
