<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BrandMark from '../components/BrandMark.vue';

const auth = useAuthStore();
const router = useRouter();

const isMobileSidebarOpen = ref(false);
const isUserMenuOpen = ref(false);

const navigationSections = [
    {
        title: 'Gestión RRHH',
        items: [
            { label: 'Panel de Control', to: '/company/dashboard', icon: 'home' },
            { label: 'Consumo & Asistencia', to: '/company/consumption', icon: 'chart' },
            { label: 'Subsidios & Copago', to: '/company/subsidies', icon: 'badge' },
        ],
    },
    {
        title: 'Nómina de Colaboradores',
        items: [
            { label: 'Comensales Activos', to: '/company/employees', icon: 'users' },
            { label: 'Centros de Costo / Turnos', to: '/company/cost-centers', icon: 'clock' },
        ],
    },
    {
        title: 'Sedes & Puntos de Retiro',
        items: [
            { label: 'Plantas y Oficinas', to: '/company/branches', icon: 'building' },
        ],
    },
    {
        title: 'Liquidación & Finanzas',
        items: [
            { label: 'Descuento por Planilla', to: '/company/payroll-deductions', icon: 'document' },
            { label: 'Historial de Facturación', to: '/company/invoices', icon: 'receipt' },
        ],
    },
];

async function logout() {
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>

<template>
    <div class="min-h-screen bg-[#f8fafc] text-slate-800 grid grid-cols-1 lg:grid-cols-[280px_1fr] antialiased">
        <!-- Sidebar Corporativo RRHH -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 lg:w-auto bg-white px-6 py-6 flex flex-col justify-between border-r border-slate-200 shadow-xl lg:shadow-none transition-transform duration-200 lg:static lg:translate-x-0"
            :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="space-y-6 overflow-y-auto">
                <div class="flex items-center justify-between">
                    <router-link :to="{ name: 'landing' }" class="focus:outline-none">
                        <BrandMark size="sm" />
                    </router-link>
                    <button
                        type="button"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 lg:hidden"
                        @click="isMobileSidebarOpen = false"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tarjeta de la Empresa Cliente -->
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5 space-y-1.5">
                    <div class="text-[10.5px] font-bold tracking-wider text-slate-400 uppercase">Portal Empresa Cliente</div>
                    <div class="text-sm font-semibold text-slate-900 truncate">
                        {{ auth.user?.company?.name || 'Empresa Cliente SpA' }}
                    </div>
                    <div class="text-xs text-slate-500 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span class="truncate">Provisto por: {{ auth.user?.tenant?.name || 'Catering MinutaFlow' }}</span>
                    </div>
                </div>

                <!-- Navegación RRHH -->
                <nav class="space-y-5">
                    <div v-for="section in navigationSections" :key="section.title" class="space-y-1">
                        <div class="px-3 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                            {{ section.title }}
                        </div>
                        <div class="space-y-0.5">
                            <router-link
                                v-for="item in section.items"
                                :key="item.label"
                                :to="item.to"
                                class="flex items-center justify-between rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors font-medium"
                                active-class="!bg-slate-900 !text-white shadow-xs"
                                @click="isMobileSidebarOpen = false"
                            >
                                <span>{{ item.label }}</span>
                            </router-link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Usuario RRHH / Salir -->
            <div class="pt-4 mt-auto border-t border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                        {{ auth.initials || 'RH' }}
                    </div>
                    <div class="min-w-0 leading-tight">
                        <div class="text-xs font-semibold text-slate-900 truncate">
                            {{ auth.user?.name || 'Admin RRHH' }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Gestor de Personas
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-xs text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                    title="Cerrar sesión"
                    @click="logout"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Overlay Móvil -->
        <div
            v-if="isMobileSidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden backdrop-blur-xs"
            @click="isMobileSidebarOpen = false"
        ></div>

        <!-- Área Central -->
        <div class="flex flex-col min-w-0 min-h-screen">
            <header class="h-16 bg-white border-b border-slate-200 px-6 lg:px-10 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 lg:hidden"
                        @click="isMobileSidebarOpen = true"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="text-xs font-medium text-slate-500">Módulo de Administración de Subsidios y Comensales</span>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Exportar Nómina / Copagos</span>
                    </button>
                </div>
            </header>

            <main class="flex-1 px-6 lg:px-10 py-8">
                <slot>
                    <router-view />
                </slot>
            </main>
        </div>
    </div>
</template>
