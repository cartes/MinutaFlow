<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../../api';

const router = useRouter();

const tenants = ref([]);
const metrics = ref({
    total_tenants: 0,
    active_tenants: 0,
    total_companies: 0,
    total_branches: 0,
    total_users: 0,
    total_orders: 0,
});
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const search = ref('');
const statusFilter = ref('');

// Modal de Creación de Catering
const isModalOpen = ref(false);
const modalError = ref(null);
const form = ref({
    name: '',
    rut: '',
    slug: '',
    billing_email: '',
    phone: '',
    is_active: true,
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

async function loadData() {
    loading.value = true;
    error.value = null;
    try {
        const [tenantsRes, metricsRes] = await Promise.all([
            api.get('/superadmin/tenants', {
                search: search.value || undefined,
                is_active: statusFilter.value !== '' ? statusFilter.value : undefined,
            }),
            api.get('/superadmin/metrics'),
        ]);
        tenants.value = tenantsRes.data || [];
        metrics.value = metricsRes.data || {};
    } catch (e) {
        error.value = e.message || 'Error al cargar las empresas concesionarias.';
    } finally {
        loading.value = false;
    }
}

const filteredTenants = computed(() => {
    return tenants.value.filter((t) => {
        if (!search.value) return true;
        const q = search.value.toLowerCase();
        return (
            t.name?.toLowerCase().includes(q) ||
            t.rut?.toLowerCase().includes(q) ||
            t.slug?.toLowerCase().includes(q) ||
            t.billing_email?.toLowerCase().includes(q)
        );
    });
});

function openCreateModal() {
    form.value = {
        name: '',
        rut: '',
        slug: '',
        billing_email: '',
        phone: '',
        is_active: true,
        admin_name: '',
        admin_email: '',
        admin_password: '',
    };
    modalError.value = null;
    isModalOpen.value = true;
}

function closeCreateModal() {
    if (saving.value) return;
    isModalOpen.value = false;
}

// Auto-generar sugerencia de slug y correo admin al escribir nombre
function onNameChange() {
    if (!form.value.slug && form.value.name) {
        form.value.slug = form.value.name
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
}

async function handleCreateTenant() {
    saving.value = true;
    modalError.value = null;
    try {
        await api.post('/superadmin/tenants', form.value);
        isModalOpen.value = false;
        await loadData();
    } catch (e) {
        modalError.value = e.errors
            ? Object.values(e.errors).flat().join(' ')
            : e.message || 'Error al registrar la empresa de catering.';
    } finally {
        saving.value = false;
    }
}

async function toggleTenantStatus(tenant) {
    const newStatus = !tenant.is_active;
    const confirmMsg = newStatus
        ? `¿Deseas reactivar a la concesionaria "${tenant.name}"?`
        : `¿Deseas pausar temporalmente a la concesionaria "${tenant.name}"? Los usuarios no podrán operar.`;
    
    if (!confirm(confirmMsg)) return;

    try {
        await api.put(`/superadmin/tenants/${tenant.id}`, {
            is_active: newStatus,
        });
        tenant.is_active = newStatus;
        if (newStatus) {
            metrics.value.active_tenants++;
        } else {
            metrics.value.active_tenants = Math.max(0, metrics.value.active_tenants - 1);
        }
    } catch (e) {
        alert(e.message || 'No se pudo actualizar el estado.');
    }
}

function goToTenantDetail(tenant) {
    router.push({ name: 'superadmin-tenant-overview', params: { tenantId: tenant.id } });
}

onMounted(() => {
    loadData();
});
</script>

<template>
    <div class="max-w-[1400px] mx-auto space-y-8">
        <!-- Encabezado de Sección -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 mb-2 border border-emerald-500/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                    Administración de Negocios & Concesiones
                </div>
                <h1 class="font-serif text-3xl md:text-4xl text-ink tracking-tight">
                    Empresas de Catering (Tenants)
                </h1>
                <p class="text-sm text-muted mt-1">
                    Supervisa y administra las empresas concesionarias registradas en la red MinutaFlow.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-ink px-4 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-ink-hover transition-all cursor-pointer"
                    @click="openCreateModal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nueva Concesionaria</span>
                </button>
            </div>
        </div>

        <!-- Tarjetas de KPIs Globales -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-faint mb-2 uppercase tracking-wider">
                    <span>Caterings Activos</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                </div>
                <div class="text-3xl font-serif font-bold text-ink">
                    {{ metrics.active_tenants }} <span class="text-sm font-sans font-normal text-muted">/ {{ metrics.total_tenants }}</span>
                </div>
                <div class="text-[12px] text-muted mt-1">Concesionarias operando</div>
            </div>

            <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-faint mb-2 uppercase tracking-wider">
                    <span>Empresas Clientes</span>
                    <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                </div>
                <div class="text-3xl font-serif font-bold text-ink">
                    {{ metrics.total_companies }}
                </div>
                <div class="text-[12px] text-muted mt-1">Contratos corporativos</div>
            </div>

            <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-faint mb-2 uppercase tracking-wider">
                    <span>Sucursales & Casinos</span>
                    <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                </div>
                <div class="text-3xl font-serif font-bold text-ink">
                    {{ metrics.total_branches }}
                </div>
                <div class="text-[12px] text-muted mt-1">Puntos de entrega activos</div>
            </div>

            <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-xs font-medium text-faint mb-2 uppercase tracking-wider">
                    <span>Pedidos Totales</span>
                    <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </span>
                </div>
                <div class="text-3xl font-serif font-bold text-ink">
                    {{ metrics.total_orders }}
                </div>
                <div class="text-[12px] text-muted mt-1">Transacciones procesadas</div>
            </div>
        </div>

        <!-- Filtros y Buscador -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-card-line shadow-xs">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-faint">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar catering por nombre, RUT o slug..."
                    class="w-full pl-9 pr-3.5 py-2 text-sm rounded-xl border border-btn-line bg-paper/60 focus:bg-white focus:border-ink outline-none transition-all"
                />
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <select
                    v-model="statusFilter"
                    @change="loadData"
                    class="w-full sm:w-auto px-3.5 py-2 text-sm rounded-xl border border-btn-line bg-white focus:border-ink outline-none text-muted font-medium"
                >
                    <option value="">Todos los estados</option>
                    <option value="true">Solo Activas</option>
                    <option value="false">Solo Pausadas</option>
                </select>

                <button
                    type="button"
                    class="p-2 rounded-xl border border-btn-line bg-white hover:bg-paper text-muted hover:text-ink transition-colors"
                    title="Recargar datos"
                    @click="loadData"
                >
                    <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Feedback de Carga / Error -->
        <div v-if="loading && !tenants.length" class="text-center py-16 text-muted">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-ink border-t-transparent mb-3"></div>
            <p class="text-sm">Cargando empresas concesionarias...</p>
        </div>

        <div v-else-if="error" class="rounded-2xl bg-rust/10 border border-rust/30 p-6 text-center text-rust">
            <p class="font-medium">{{ error }}</p>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-rust px-4 py-2 text-xs font-semibold text-white hover:opacity-90"
                @click="loadData"
            >Reintentar</button>
        </div>

        <!-- Tabla de Concesionarias -->
        <div v-else class="rounded-2xl border border-card-line bg-white overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-ink">
                    <thead class="bg-paper/80 border-b border-line text-[11px] uppercase tracking-wider text-faint font-semibold">
                        <tr>
                            <th class="px-6 py-4">Empresa / Concesionaria</th>
                            <th class="px-6 py-4">RUT & Slug</th>
                            <th class="px-6 py-4">Admin Inicial</th>
                            <th class="px-6 py-4 text-center">Empresas</th>
                            <th class="px-6 py-4 text-center">Sucursales</th>
                            <th class="px-6 py-4 text-center">Pedidos</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-row-line">
                        <tr
                            v-for="tenant in filteredTenants"
                            :key="tenant.id"
                            class="hover:bg-paper/40 transition-colors cursor-pointer"
                            title="Ver detalle de la concesionaria"
                            @click="goToTenantDetail(tenant)"
                        >
                            <!-- Nombre & Contacto -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-ink text-[15px]">
                                    {{ tenant.name }}
                                </div>
                                <div class="text-xs text-muted mt-0.5">
                                    {{ tenant.billing_email }} {{ tenant.phone ? `· ${tenant.phone}` : '' }}
                                </div>
                            </td>

                            <!-- RUT y Slug -->
                            <td class="px-6 py-4">
                                <div class="font-mono text-xs text-ink font-medium">
                                    {{ tenant.rut }}
                                </div>
                                <div class="text-[11px] text-faint font-mono">
                                    /{{ tenant.slug }}
                                </div>
                            </td>

                            <!-- Administrador -->
                            <td class="px-6 py-4">
                                <template v-if="tenant.users?.[0]">
                                    <div class="font-medium text-xs text-ink">
                                        {{ tenant.users[0].name }}
                                    </div>
                                    <div class="text-[11px] text-muted">
                                        {{ tenant.users[0].email }}
                                    </div>
                                </template>
                                <span v-else class="text-xs text-faint italic">
                                    Sin admin asignado
                                </span>
                            </td>

                            <!-- Contadores -->
                            <td class="px-6 py-4 text-center font-medium">
                                <span class="rounded-md bg-paper px-2.5 py-1 text-xs text-ink border border-line">
                                    {{ tenant.companies_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-medium">
                                <span class="rounded-md bg-paper px-2.5 py-1 text-xs text-ink border border-line">
                                    {{ tenant.branches_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-medium">
                                <span class="rounded-md bg-paper px-2.5 py-1 text-xs text-ink border border-line">
                                    {{ tenant.orders_count }}
                                </span>
                            </td>

                            <!-- Estado Activo / Pausado -->
                            <td class="px-6 py-4 text-center">
                                <span
                                    v-if="tenant.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                    Activo
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                    Pausado
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg px-2.5 py-1 text-xs font-medium border border-btn-line bg-white text-ink hover:bg-paper hover:border-ink/40 transition-colors cursor-pointer"
                                        @click.stop="goToTenantDetail(tenant)"
                                    >
                                        Ver Detalles
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg px-2.5 py-1 text-xs font-medium border border-btn-line bg-white hover:bg-paper transition-colors cursor-pointer"
                                        :class="tenant.is_active ? 'text-amber-700 hover:border-amber-300' : 'text-emerald-700 hover:border-emerald-300'"
                                        @click.stop="toggleTenantStatus(tenant)"
                                    >
                                        {{ tenant.is_active ? 'Pausar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!filteredTenants.length">
                            <td colspan="8" class="text-center py-12 text-muted text-sm">
                                No se encontraron empresas de catering que coincidan con la búsqueda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de Creación de Empresa Concesionaria -->
        <Teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-black/60 backdrop-blur-xs"
                @mousedown.self="closeCreateModal"
            >
                <div class="relative w-full max-w-[540px] rounded-3xl border border-white/40 bg-white p-7 sm:p-8 shadow-2xl z-10 animate-scale-in">
                    <!-- Botón cerrar -->
                    <button
                        type="button"
                        class="absolute top-5 right-5 flex h-8 w-8 items-center justify-center rounded-full bg-paper text-muted hover:bg-line hover:text-ink transition-colors"
                        @click="closeCreateModal"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="mb-6">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 mb-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                            Nueva Concesionaria
                        </div>
                        <h2 class="font-serif text-2xl text-ink font-bold tracking-tight">
                            Registrar Empresa de Catering
                        </h2>
                        <p class="text-xs text-muted mt-1">
                            Crea una nueva organización independiente en la plataforma MinutaFlow.
                        </p>
                    </div>

                    <form class="space-y-4" @submit.prevent="handleCreateTenant">
                        <!-- Nombre del Catering -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-ink">
                                Razón Social o Nombre de Fantasía *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ej: Casino Central Gourmet SpA"
                                class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                @input="onNameChange"
                            />
                        </div>

                        <!-- RUT y Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-ink">
                                    RUT Concesionaria *
                                </label>
                                <input
                                    v-model="form.rut"
                                    type="text"
                                    required
                                    placeholder="77.654.321-0"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-ink">
                                    Subdominio / Slug
                                </label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="casino-central"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                />
                            </div>
                        </div>

                        <!-- Correo facturación y teléfono -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-ink">
                                    Correo de Facturación *
                                </label>
                                <input
                                    v-model="form.billing_email"
                                    type="email"
                                    required
                                    placeholder="facturas@catering.cl"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                />
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-ink">
                                    Teléfono de Contacto
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="+56 9 8765 4321"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                />
                            </div>
                        </div>

                        <!-- Sección Administrador Inicial -->
                        <div class="pt-3 border-t border-line space-y-3">
                            <div class="text-xs font-bold text-ink uppercase tracking-wider">
                                Usuario Administrador Inicial (Opcional)
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block text-xs text-muted">Nombre Admin</label>
                                    <input
                                        v-model="form.admin_name"
                                        type="text"
                                        placeholder="Juan Pérez"
                                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs text-muted">Correo Acceso Admin</label>
                                    <input
                                        v-model="form.admin_email"
                                        type="email"
                                        placeholder="admin@catering.cl"
                                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                    />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs text-muted">Contraseña Inicial</label>
                                <input
                                    v-model="form.admin_password"
                                    type="password"
                                    placeholder="Por defecto: password"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink placeholder:text-faint focus:bg-white focus:border-ink outline-none"
                                />
                            </div>
                        </div>

                        <!-- Feedback de error en modal -->
                        <div v-if="modalError" class="rounded-xl bg-rust/10 p-3 text-xs text-rust font-medium">
                            {{ modalError }}
                        </div>

                        <!-- Botones de Acción -->
                        <div class="pt-4 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-xl border border-btn-line px-4 py-2.5 text-sm font-medium text-muted hover:bg-paper"
                                :disabled="saving"
                                @click="closeCreateModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="saving"
                                class="rounded-xl bg-ink px-5 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-ink-hover disabled:opacity-50"
                            >
                                {{ saving ? 'Creando Concesionaria...' : 'Crear Concesionaria' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
