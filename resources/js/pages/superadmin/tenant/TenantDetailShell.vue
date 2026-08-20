<script setup>
import { watch, onMounted } from 'vue';
import { useSuperadminTenantStore } from '../../../stores/superadminTenant';

const props = defineProps({
    tenantId: { type: String, required: true },
});

const store = useSuperadminTenantStore();

async function load() {
    try {
        await store.fetchTenant(props.tenantId, { force: true });
    } catch {
        // El error queda registrado en el store y se muestra abajo
    }
}

onMounted(load);
watch(() => props.tenantId, load);
</script>

<template>
    <div class="max-w-[1400px] mx-auto space-y-8">
        <!-- Breadcrumb de navegación -->
        <nav class="flex items-center gap-2 text-xs text-muted">
            <router-link :to="{ name: 'superadmin-tenants' }" class="hover:text-ink font-medium transition-colors">
                Empresas de Catering
            </router-link>
            <svg class="w-3.5 h-3.5 text-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-semibold text-ink truncate max-w-[280px]">
                {{ store.tenantName || 'Detalle' }}
            </span>
        </nav>

        <!-- Estado de carga inicial -->
        <div v-if="store.loading && !store.tenant" class="text-center py-20 text-muted">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-ink border-t-transparent mb-3"></div>
            <p class="text-sm">Cargando concesionaria...</p>
        </div>

        <!-- Error de carga -->
        <div v-else-if="store.error && !store.tenant" class="rounded-2xl bg-rust/10 border border-rust/30 p-6 text-center text-rust">
            <p class="font-medium">{{ store.error }}</p>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-rust px-4 py-2 text-xs font-semibold text-white hover:opacity-90"
                @click="load"
            >Reintentar</button>
        </div>

        <!-- Contenido de la sub-sección activa -->
        <router-view v-else />
    </div>
</template>
