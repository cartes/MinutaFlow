<script setup>
import { computed } from 'vue';
import { useSuperadminTenantStore } from '../../../stores/superadminTenant';

const store = useSuperadminTenantStore();
const tenant = computed(() => store.tenant ?? {});

const admins = computed(() =>
    (tenant.value.users ?? []).filter((u) => u.role === 'tenant_admin'),
);

const kpis = computed(() => [
    { label: 'Empresas Clientes', value: tenant.value.companies_count ?? 0, to: 'superadmin-tenant-companies', color: 'text-blue-600 bg-blue-50' },
    { label: 'Sucursales', value: tenant.value.branches_count ?? 0, to: 'superadmin-tenant-branches', color: 'text-amber-600 bg-amber-50' },
    { label: 'Usuarios', value: tenant.value.users_count ?? 0, to: 'superadmin-tenant-users', color: 'text-purple-600 bg-purple-50' },
    { label: 'Pedidos Totales', value: tenant.value.orders_count ?? 0, to: 'superadmin-tenant-reports', color: 'text-emerald-600 bg-emerald-50' },
]);

const createdAt = computed(() => {
    if (!tenant.value.created_at) return '—';
    return new Intl.DateTimeFormat('es-CL', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(tenant.value.created_at));
});
</script>

<template>
    <div class="space-y-8">
        <!-- Encabezado del Tenant -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-serif text-3xl md:text-4xl text-ink tracking-tight">
                        {{ tenant.name }}
                    </h1>
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
                </div>
                <p class="text-sm text-muted mt-1 font-mono">
                    {{ tenant.rut }} · /{{ tenant.slug }}
                </p>
            </div>

            <router-link
                :to="{ name: 'superadmin-tenant-edit' }"
                class="inline-flex items-center gap-2 rounded-xl bg-ink px-4 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-ink-hover transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Editar Ficha</span>
            </router-link>
        </div>

        <!-- KPIs del Tenant (clickeables hacia sub-secciones) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <router-link
                v-for="kpi in kpis"
                :key="kpi.label"
                :to="{ name: kpi.to }"
                class="rounded-2xl border border-card-line bg-white p-5 shadow-xs hover:border-ink/30 hover:shadow-sm transition-all group"
            >
                <div class="flex items-center justify-between text-xs font-medium text-faint mb-2 uppercase tracking-wider">
                    <span>{{ kpi.label }}</span>
                    <span class="p-1.5 rounded-lg" :class="kpi.color">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </div>
                <div class="text-3xl font-serif font-bold text-ink">{{ kpi.value }}</div>
                <div class="text-[12px] text-muted mt-1 group-hover:text-ink transition-colors">Ver detalle →</div>
            </router-link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Ficha de Datos -->
            <div class="rounded-2xl border border-card-line bg-white p-6 shadow-xs space-y-4">
                <h2 class="text-sm font-bold text-ink uppercase tracking-wider">Ficha de la Concesionaria</h2>
                <dl class="divide-y divide-row-line text-sm">
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Razón Social</dt>
                        <dd class="font-medium text-ink text-right">{{ tenant.name }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">RUT</dt>
                        <dd class="font-mono text-ink text-right">{{ tenant.rut }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Slug / Subdominio</dt>
                        <dd class="font-mono text-ink text-right">/{{ tenant.slug }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Correo de Facturación</dt>
                        <dd class="font-medium text-ink text-right">{{ tenant.billing_email }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Teléfono</dt>
                        <dd class="font-medium text-ink text-right">{{ tenant.phone || '—' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Zona Horaria</dt>
                        <dd class="font-medium text-ink text-right">{{ tenant.timezone }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Moneda</dt>
                        <dd class="font-medium text-ink text-right">{{ tenant.currency }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Plan</dt>
                        <dd class="font-medium text-ink text-right capitalize">{{ tenant.settings?.plan || 'standard' }}</dd>
                    </div>
                    <div class="py-2.5 flex justify-between gap-4">
                        <dt class="text-muted">Registrada el</dt>
                        <dd class="font-medium text-ink text-right">{{ createdAt }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Administradores del Catering -->
            <div class="rounded-2xl border border-card-line bg-white p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-ink uppercase tracking-wider">Administradores</h2>
                    <router-link :to="{ name: 'superadmin-tenant-users' }" class="text-xs font-medium text-muted hover:text-ink">
                        Ver todos los usuarios →
                    </router-link>
                </div>

                <div v-if="admins.length" class="space-y-3">
                    <div
                        v-for="admin in admins"
                        :key="admin.id"
                        class="flex items-center justify-between gap-3 rounded-xl border border-line bg-paper/40 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-ink truncate">{{ admin.name }}</div>
                            <div class="text-xs text-muted truncate">{{ admin.email }} {{ admin.phone ? `· ${admin.phone}` : '' }}</div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold border"
                            :class="admin.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                        >
                            {{ admin.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-sm text-faint italic py-4 text-center">
                    Esta concesionaria aún no tiene administradores asignados.
                </p>

                <!-- Empresas Clientes (vista rápida) -->
                <div class="pt-2 border-t border-line">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Empresas a las que presta servicio</h3>
                        <router-link :to="{ name: 'superadmin-tenant-companies' }" class="text-xs font-medium text-muted hover:text-ink">
                            Ver todas →
                        </router-link>
                    </div>
                    <div v-if="tenant.companies?.length" class="space-y-2">
                        <div
                            v-for="company in tenant.companies.slice(0, 5)"
                            :key="company.id"
                            class="flex items-center justify-between gap-3 text-sm py-1.5"
                        >
                            <span class="font-medium text-ink truncate">{{ company.name }}</span>
                            <span class="shrink-0 text-xs text-muted">
                                {{ company.branches?.length ?? 0 }} sucursal(es)
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-faint italic py-2 text-center">
                        Sin empresas clientes registradas todavía.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
