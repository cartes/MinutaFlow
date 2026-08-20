<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BrandMark from '../components/BrandMark.vue';

const auth = useAuthStore();
const router = useRouter();

const isMobileSidebarOpen = ref(false);

const navigationSections = [
    {
        title: 'Gestión de Plataforma',
        items: [
            {
                label: 'Empresas Concesionarias',
                to: { name: 'superadmin-tenants' },
                icon: 'building',
                badge: 'Tenants',
            },
            {
                label: 'Métricas de Red',
                to: { name: 'superadmin-tenants' }, // direct to dashboard / metrics
                icon: 'chart',
            },
        ],
    },
    {
        title: 'Infraestructura SaaS',
        items: [
            {
                label: 'Configuración Global',
                to: { name: 'superadmin-tenants' },
                icon: 'settings',
            },
            {
                label: 'Auditoría & Logs',
                to: { name: 'superadmin-tenants' },
                icon: 'shield',
            },
        ],
    },
];

const todayFormatted = computed(() => {
    return new Intl.DateTimeFormat('es-CL', {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date());
});

async function logout() {
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>

<template>
    <div class="min-h-screen bg-[#f3f0e8] text-ink grid grid-cols-1 lg:grid-cols-[290px_1fr] antialiased">
        <!-- Sidebar Super Admin (Tema Exclusivo Plataforma) -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 lg:w-auto bg-[#1b1a17] text-[#f7f4ec] px-6 py-6 flex flex-col justify-between border-r border-[#333029] shadow-2xl lg:shadow-none transition-transform duration-200 lg:static lg:translate-x-0"
            :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="space-y-6 overflow-y-auto pr-1">
                <!-- Header con Logo y Badge SuperAdmin -->
                <div class="flex items-center justify-between">
                    <router-link :to="{ name: 'superadmin-tenants' }" class="focus:outline-none flex items-center gap-2">
                        <div class="bg-white/10 p-1.5 rounded-xl backdrop-blur-md">
                            <BrandMark size="sm" />
                        </div>
                    </router-link>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-[#8a857a] hover:text-white hover:bg-white/10 lg:hidden"
                        @click="isMobileSidebarOpen = false"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tarjeta Identificadora de Rol de Plataforma -->
                <div class="rounded-2xl bg-gradient-to-br from-[#2c2a24] to-[#201f1b] border border-[#3f3b33] p-3.5 shadow-inner">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10.5px] font-bold tracking-wider text-[#a89f8d] uppercase">Consola Global</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold text-emerald-400 border border-emerald-500/30">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            SaaS Cloud
                        </span>
                    </div>
                    <div class="text-sm font-semibold text-white">
                        Administración MinutaFlow
                    </div>
                    <div class="text-xs text-[#8a857a] mt-0.5">
                        Control multi-empresa & catering
                    </div>
                </div>

                <!-- Navegación de Super Admin -->
                <nav class="space-y-5">
                    <div v-for="section in navigationSections" :key="section.title" class="space-y-1.5">
                        <div class="px-3 text-[10.5px] font-bold tracking-wider text-[#736e64] uppercase">
                            {{ section.title }}
                        </div>
                        <div class="space-y-1">
                            <router-link
                                v-for="item in section.items"
                                :key="item.label"
                                :to="item.to"
                                class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-sm text-[#b8b3a7] hover:bg-white/10 hover:text-white transition-all font-medium"
                                active-class="!bg-white/15 !text-white !font-semibold shadow-xs border border-white/10"
                                @click="isMobileSidebarOpen = false"
                            >
                                <div class="flex items-center gap-2.5">
                                    <svg v-if="item.icon === 'building'" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'chart'" class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'settings'" class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>{{ item.label }}</span>
                                </div>
                                <span
                                    v-if="item.badge"
                                    class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-300"
                                >
                                    {{ item.badge }}
                                </span>
                            </router-link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Footer de Usuario Super Admin -->
            <div class="pt-4 mt-auto border-t border-[#333029] flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-xs font-bold text-white shadow-sm">
                        SA
                    </div>
                    <div class="min-w-0 leading-tight">
                        <div class="text-xs font-semibold text-white truncate">
                            {{ auth.user?.name || 'Super Admin' }}
                        </div>
                        <div class="text-[11px] text-[#8a857a] truncate font-mono">
                            {{ auth.user?.email || 'admin@minutaflow.cl' }}
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-2 text-[#8a857a] hover:text-white hover:bg-white/10 transition-colors"
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
            class="fixed inset-0 z-40 bg-black/60 lg:hidden backdrop-blur-xs"
            @click="isMobileSidebarOpen = false"
        ></div>

        <!-- Área de Trabajo Principal -->
        <div class="flex flex-col min-w-0 min-h-screen">
            <!-- Topbar Global -->
            <header class="h-16 bg-white/80 backdrop-blur-md border-b border-line px-6 lg:px-11 flex items-center justify-between sticky top-0 z-30">
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
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-ink px-2.5 py-1 text-[11px] font-semibold text-white">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            Entorno Global
                        </span>
                        <span class="text-xs text-faint capitalize hidden sm:inline">
                            {{ todayFormatted }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 rounded-full bg-cream px-3 py-1 text-xs text-muted border border-btn-line">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>APIs & Microservicios Operativos</span>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 px-6 lg:px-11 py-8">
                <slot>
                    <router-view />
                </slot>
            </main>
        </div>
    </div>
</template>
