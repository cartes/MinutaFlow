<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BrandMark from '../components/BrandMark.vue';

const auth = useAuthStore();
const router = useRouter();

const isMobileSidebarOpen = ref(false);
const isUserMenuOpen = ref(false);

const roleLabels = {
    super_admin: 'Super Admin',
    tenant_admin: 'Administrador Catering',
    kitchen_operator: 'Jefe de Cocina / Operador',
    company_admin: 'RRHH / Cliente',
    employee: 'Comensal',
};

const navigationSections = [
    {
        title: 'Operación Diaria',
        items: [
            { label: 'Panel del Día', to: { name: 'panel' }, badge: 'Hoy' },
            { label: 'Despacho & Packing List', to: '/app/despacho' },
            { label: 'Escaneo QR Entrega', to: '/app/escaneo' },
        ],
    },
    {
        title: 'Planificación',
        items: [
            { label: 'Planificador de Menús', to: { name: 'menus' } },
            { label: 'Catálogo de Platos', to: { name: 'platos' } },
        ],
    },
    {
        title: 'Clientes & Red',
        items: [
            { label: 'Empresas Clientes', to: '/app/empresas' },
            { label: 'Sucursales & Casinos', to: '/app/sucursales' },
        ],
    },
    {
        title: 'Gestión & Reportes',
        items: [
            { label: 'Consumos & Mermas', to: '/app/reportes' },
            { label: 'Ajustes del Catering', to: '/app/ajustes' },
        ],
    },
];

const todayFormatted = computed(() => {
    return new Intl.DateTimeFormat('es-CL', {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
    }).format(new Date());
});

async function logout() {
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>

<template>
    <div class="min-h-screen bg-cream text-ink grid grid-cols-1 lg:grid-cols-[280px_1fr] antialiased">
        <!-- Sidebar para Escritorio y Drawer Móvil -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 lg:w-auto bg-sand px-6 py-6 flex flex-col justify-between border-r border-line shadow-xl lg:shadow-none transition-transform duration-200 lg:static lg:translate-x-0"
            :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="space-y-6 overflow-y-auto pr-1">
                <!-- Header Sidebar con Logo y Nombre Tenant -->
                <div class="flex items-center justify-between">
                    <router-link :to="{ name: 'landing' }" class="focus:outline-none">
                        <BrandMark size="sm" />
                    </router-link>
                    <button
                        type="button"
                        class="p-1 rounded-lg text-sand-text hover:bg-black/5 lg:hidden"
                        @click="isMobileSidebarOpen = false"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tarjeta de Identificación de Concesión -->
                <div class="rounded-xl bg-sand-deep/40 border border-sand-deep/60 p-3">
                    <div class="text-[11px] font-semibold tracking-wider text-sand-muted uppercase">Concesionaria</div>
                    <div class="text-sm font-semibold text-ink truncate">
                        {{ auth.user?.tenant?.name || 'Catering Central' }}
                    </div>
                    <div class="mt-1 flex items-center gap-1.5 text-xs text-sand-text">
                        <span class="h-2 w-2 rounded-full bg-green"></span>
                        <span>Servicio Activo</span>
                    </div>
                </div>

                <!-- Navegación Agrupada -->
                <nav class="space-y-5">
                    <div v-for="section in navigationSections" :key="section.title" class="space-y-1">
                        <div class="px-3 text-[11px] font-bold tracking-wider text-sand-muted uppercase">
                            {{ section.title }}
                        </div>
                        <div class="space-y-0.5">
                            <router-link
                                v-for="item in section.items"
                                :key="item.label"
                                :to="item.to"
                                class="flex items-center justify-between rounded-xl px-3 py-2 text-sm text-sand-text hover:bg-white/60 hover:text-ink transition-colors font-medium"
                                active-class="!bg-white font-semibold !text-ink shadow-xs"
                                @click="isMobileSidebarOpen = false"
                            >
                                <span>{{ item.label }}</span>
                                <span
                                    v-if="item.badge"
                                    class="rounded-full bg-green/15 px-2 py-0.5 text-[10px] font-semibold text-green-deep"
                                >
                                    {{ item.badge }}
                                </span>
                            </router-link>
                        </div>
                    </div>
                </nav>

                <!-- Inyección Contextual de Página -->
                <div id="sidebar-extra" class="grid gap-5 pt-2"></div>
            </div>

            <!-- Footer de Usuario en Sidebar -->
            <div class="pt-4 mt-auto border-t border-sand-deep/60 flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sand-deep text-xs font-bold text-sand-text">
                        {{ auth.initials || 'C' }}
                    </div>
                    <div class="min-w-0 leading-tight">
                        <div class="text-xs font-semibold text-ink truncate">
                            {{ auth.user?.name || 'Operador' }}
                        </div>
                        <div class="text-[11px] text-sand-muted truncate">
                            {{ roleLabels[auth.user?.role] || 'Catering' }}
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-xs text-sand-muted hover:text-ink hover:bg-white/40 transition-colors"
                    title="Cerrar sesión"
                    @click="logout"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Overlay Fondo Móvil -->
        <div
            v-if="isMobileSidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden backdrop-blur-xs"
            @click="isMobileSidebarOpen = false"
        ></div>

        <!-- Área Principal de Trabajo -->
        <div class="flex flex-col min-w-0 min-h-screen">
            <!-- Topbar de la Aplicación -->
            <header class="h-16 bg-white/70 backdrop-blur-md border-b border-line px-6 lg:px-11 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="p-2 rounded-lg text-muted hover:text-ink hover:bg-paper lg:hidden"
                        @click="isMobileSidebarOpen = true"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="text-xs text-faint capitalize hidden sm:block">
                        {{ todayFormatted }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Estado de servicio -->
                    <div class="hidden sm:flex items-center gap-2 rounded-full bg-green-soft px-3 py-1 text-xs text-green font-medium">
                        <span class="h-2 w-2 rounded-full bg-green animate-pulse"></span>
                        <span>Cocina Central en Línea</span>
                    </div>

                    <!-- Enlace directo al lector de entrega QR -->
                    <router-link
                        to="/app/escaneo"
                        class="flex items-center gap-1.5 rounded-lg border border-btn-line bg-white px-3 py-1.5 text-xs font-medium text-ink hover:bg-paper transition-all"
                    >
                        <svg class="w-3.5 h-3.5 text-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        <span>Escanear QR</span>
                    </router-link>
                </div>
            </header>

            <!-- Main Content Slot -->
            <main class="flex-1 px-6 lg:px-11 py-8">
                <slot>
                    <router-view />
                </slot>
            </main>
        </div>
    </div>
</template>
