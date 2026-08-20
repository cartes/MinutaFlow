<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BrandMark from '../components/BrandMark.vue';

const auth = useAuthStore();
const router = useRouter();

const navItems = [
    { label: 'Menú Hoy', to: '/comensal/hoy', icon: 'dish' },
    { label: 'Plan Semanal', to: '/comensal/semana', icon: 'calendar' },
    { label: 'Mi QR', to: '/comensal/ticket', icon: 'qr', highlight: true },
    { label: 'Mi Perfil', to: '/comensal/perfil', icon: 'user' },
];

async function logout() {
    await auth.logout();
    router.push({ name: 'landing' });
}
</script>

<template>
    <div class="min-h-screen bg-cream text-ink flex flex-col antialiased pb-20 md:pb-0">
        <!-- Header Superior Comensal -->
        <header class="bg-white/80 backdrop-blur-md border-b border-line px-4 sm:px-8 py-3.5 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <BrandMark size="sm" />
                <div class="hidden sm:block text-xs text-muted border-l border-line pl-3">
                    {{ auth.user?.company?.name || 'Comedor Corporativo' }}
                </div>
            </div>

            <!-- Navegación Escritorio -->
            <nav class="hidden md:flex items-center gap-1">
                <router-link
                    v-for="item in navItems"
                    :key="item.label"
                    :to="item.to"
                    class="px-3.5 py-1.5 rounded-xl text-sm font-medium text-muted hover:text-ink hover:bg-paper transition-all"
                    active-class="!bg-green-soft !text-green font-semibold"
                >
                    {{ item.label }}
                </router-link>
            </nav>

            <!-- Acciones de Usuario -->
            <div class="flex items-center gap-3">
                <div class="text-right leading-tight">
                    <div class="text-xs font-semibold text-ink">{{ auth.user?.name || 'Colaborador' }}</div>
                    <div class="text-[11px] text-green font-medium">Subsidio Activo</div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-xs text-muted hover:text-ink hover:bg-paper transition-colors"
                    title="Cerrar sesión"
                    @click="logout"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="flex-1 mx-auto w-full max-w-3xl px-4 sm:px-6 py-6">
            <slot>
                <router-view />
            </slot>
        </main>

        <!-- Barra de Navegación Inferior Móvil (Bottom Navigation) -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-line px-2 py-2 flex items-center justify-around z-40 shadow-lg">
            <router-link
                to="/comensal/hoy"
                class="flex flex-col items-center justify-center py-1 px-3 rounded-lg text-muted hover:text-ink transition-colors"
                active-class="!text-green font-bold"
            >
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px]">Hoy</span>
            </router-link>

            <router-link
                to="/comensal/semana"
                class="flex flex-col items-center justify-center py-1 px-3 rounded-lg text-muted hover:text-ink transition-colors"
                active-class="!text-green font-bold"
            >
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-[10px]">Semana</span>
            </router-link>

            <router-link
                to="/comensal/ticket"
                class="flex flex-col items-center justify-center -mt-5 bg-green text-white p-3 rounded-full shadow-lg hover:bg-green-hover transition-transform active:scale-95"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </router-link>

            <router-link
                to="/comensal/perfil"
                class="flex flex-col items-center justify-center py-1 px-3 rounded-lg text-muted hover:text-ink transition-colors"
                active-class="!text-green font-bold"
            >
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[10px]">Perfil</span>
            </router-link>
        </nav>
    </div>
</template>
