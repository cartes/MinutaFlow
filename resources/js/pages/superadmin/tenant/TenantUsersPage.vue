<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../../../api';

const route = useRoute();
const users = ref([]);
const loading = ref(true);
const error = ref(null);
const search = ref('');
const roleFilter = ref('');

const ROLE_LABELS = {
    tenant_admin: { label: 'Admin Catering', classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    kitchen_operator: { label: 'Cocina / Despacho', classes: 'bg-orange-50 text-orange-700 border-orange-200' },
    company_admin: { label: 'RRHH Empresa', classes: 'bg-blue-50 text-blue-700 border-blue-200' },
    employee: { label: 'Comensal', classes: 'bg-paper text-muted border-line' },
    super_admin: { label: 'Super Admin', classes: 'bg-purple-50 text-purple-700 border-purple-200' },
};

async function loadUsers() {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.get(`/superadmin/tenants/${route.params.tenantId}/users`, {
            role: roleFilter.value || undefined,
        });
        users.value = res.data || [];
    } catch (e) {
        error.value = e.message || 'Error al cargar los usuarios.';
    } finally {
        loading.value = false;
    }
}

const filteredUsers = computed(() => {
    if (!search.value) return users.value;
    const q = search.value.toLowerCase();
    return users.value.filter(
        (u) =>
            u.name?.toLowerCase().includes(q) ||
            u.email?.toLowerCase().includes(q) ||
            u.rut?.toLowerCase().includes(q),
    );
});

function roleBadge(role) {
    return ROLE_LABELS[role] ?? { label: role, classes: 'bg-paper text-muted border-line' };
}

onMounted(loadUsers);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="font-serif text-2xl md:text-3xl text-ink tracking-tight">Usuarios & Accesos</h1>
                <p class="text-sm text-muted mt-1">
                    Administradores, personal de cocina, RRHH y comensales bajo esta concesionaria.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-faint">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre, correo o RUT..."
                        class="w-full pl-9 pr-3.5 py-2 text-sm rounded-xl border border-btn-line bg-white focus:border-ink outline-none transition-all"
                    />
                </div>

                <select
                    v-model="roleFilter"
                    @change="loadUsers"
                    class="w-full sm:w-auto px-3.5 py-2 text-sm rounded-xl border border-btn-line bg-white focus:border-ink outline-none text-muted font-medium"
                >
                    <option value="">Todos los roles</option>
                    <option value="tenant_admin">Admin Catering</option>
                    <option value="kitchen_operator">Cocina / Despacho</option>
                    <option value="company_admin">RRHH Empresa</option>
                    <option value="employee">Comensales</option>
                </select>
            </div>
        </div>

        <div v-if="loading" class="text-center py-16 text-muted">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-ink border-t-transparent mb-3"></div>
            <p class="text-sm">Cargando usuarios...</p>
        </div>

        <div v-else-if="error" class="rounded-2xl bg-rust/10 border border-rust/30 p-6 text-center text-rust">
            <p class="font-medium">{{ error }}</p>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-rust px-4 py-2 text-xs font-semibold text-white hover:opacity-90"
                @click="loadUsers"
            >Reintentar</button>
        </div>

        <div v-else class="rounded-2xl border border-card-line bg-white overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-ink">
                    <thead class="bg-paper/80 border-b border-line text-[11px] uppercase tracking-wider text-faint font-semibold">
                        <tr>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">RUT</th>
                            <th class="px-6 py-4 text-center">Rol</th>
                            <th class="px-6 py-4">Empresa</th>
                            <th class="px-6 py-4">Sucursal</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-row-line">
                        <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-paper/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-ink">{{ user.name }}</div>
                                <div class="text-xs text-muted mt-0.5">{{ user.email }} {{ user.phone ? `· ${user.phone}` : '' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-muted">{{ user.rut || '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold border"
                                    :class="roleBadge(user.role).classes"
                                >
                                    {{ roleBadge(user.role).label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">{{ user.company?.name || '—' }}</td>
                            <td class="px-6 py-4 text-xs">{{ user.branch?.name || '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border"
                                    :class="user.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-amber-50 text-amber-700 border-amber-200'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-600' : 'bg-amber-600'"></span>
                                    {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!filteredUsers.length">
                            <td colspan="6" class="text-center py-12 text-muted text-sm">
                                No se encontraron usuarios con los filtros aplicados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
