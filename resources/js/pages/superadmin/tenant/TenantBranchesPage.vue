<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../../../api';

const route = useRoute();
const branches = ref([]);
const loading = ref(true);
const error = ref(null);
const search = ref('');

async function loadBranches() {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.get(`/superadmin/tenants/${route.params.tenantId}/branches`);
        branches.value = res.data || [];
    } catch (e) {
        error.value = e.message || 'Error al cargar las sucursales.';
    } finally {
        loading.value = false;
    }
}

const filteredBranches = computed(() => {
    if (!search.value) return branches.value;
    const q = search.value.toLowerCase();
    return branches.value.filter(
        (b) =>
            b.name?.toLowerCase().includes(q) ||
            b.address?.toLowerCase().includes(q) ||
            b.commune?.toLowerCase().includes(q) ||
            b.company?.name?.toLowerCase().includes(q),
    );
});

function deliveryWindow(branch) {
    if (!branch.delivery_time_start || !branch.delivery_time_end) return '—';
    return `${branch.delivery_time_start.slice(0, 5)} – ${branch.delivery_time_end.slice(0, 5)}`;
}

onMounted(loadBranches);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl md:text-3xl text-ink tracking-tight">Sucursales & Casinos</h1>
                <p class="text-sm text-muted mt-1">
                    Puntos de entrega y sedes donde esta concesionaria despacha alimentación.
                </p>
            </div>

            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-faint">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por sede, comuna o empresa..."
                    class="w-full pl-9 pr-3.5 py-2 text-sm rounded-xl border border-btn-line bg-white focus:border-ink outline-none transition-all"
                />
            </div>
        </div>

        <div v-if="loading" class="text-center py-16 text-muted">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-ink border-t-transparent mb-3"></div>
            <p class="text-sm">Cargando sucursales...</p>
        </div>

        <div v-else-if="error" class="rounded-2xl bg-rust/10 border border-rust/30 p-6 text-center text-rust">
            <p class="font-medium">{{ error }}</p>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-rust px-4 py-2 text-xs font-semibold text-white hover:opacity-90"
                @click="loadBranches"
            >Reintentar</button>
        </div>

        <div v-else class="rounded-2xl border border-card-line bg-white overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-ink">
                    <thead class="bg-paper/80 border-b border-line text-[11px] uppercase tracking-wider text-faint font-semibold">
                        <tr>
                            <th class="px-6 py-4">Sucursal / Sede</th>
                            <th class="px-6 py-4">Empresa Cliente</th>
                            <th class="px-6 py-4">Ubicación</th>
                            <th class="px-6 py-4">Contacto</th>
                            <th class="px-6 py-4 text-center">Ventana Entrega</th>
                            <th class="px-6 py-4 text-center">Comensales</th>
                            <th class="px-6 py-4 text-center">Pedidos</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-row-line">
                        <tr v-for="branch in filteredBranches" :key="branch.id" class="hover:bg-paper/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-ink">{{ branch.name }}</div>
                                <div v-if="branch.delivery_notes" class="text-[11px] text-faint mt-0.5 max-w-[220px] truncate" :title="branch.delivery_notes">
                                    {{ branch.delivery_notes }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">{{ branch.company?.name || '—' }}</td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-ink">{{ branch.address || '—' }}</div>
                                <div class="text-[11px] text-muted">
                                    {{ [branch.commune, branch.region].filter(Boolean).join(', ') }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-medium text-ink">{{ branch.contact_name || '—' }}</div>
                                <div class="text-[11px] text-muted">{{ branch.contact_phone || '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center text-xs font-mono text-muted">
                                {{ deliveryWindow(branch) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="rounded-md bg-paper px-2.5 py-1 text-xs border border-line">{{ branch.users_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="rounded-md bg-paper px-2.5 py-1 text-xs border border-line">{{ branch.orders_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border"
                                    :class="branch.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-amber-50 text-amber-700 border-amber-200'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="branch.is_active ? 'bg-emerald-600' : 'bg-amber-600'"></span>
                                    {{ branch.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!filteredBranches.length">
                            <td colspan="8" class="text-center py-12 text-muted text-sm">
                                No hay sucursales registradas para esta concesionaria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
