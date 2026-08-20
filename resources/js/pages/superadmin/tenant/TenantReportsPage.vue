<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../../../api';

const route = useRoute();
const report = ref(null);
const loading = ref(true);
const error = ref(null);

const STATUS_META = {
    confirmed: { label: 'Confirmados', bar: 'bg-blue-500' },
    delivered: { label: 'Entregados', bar: 'bg-emerald-500' },
    cancelled: { label: 'Cancelados', bar: 'bg-amber-500' },
    no_show: { label: 'No Retirados', bar: 'bg-rose-500' },
};

const ROLE_LABELS = {
    tenant_admin: 'Admin Catering',
    kitchen_operator: 'Cocina / Despacho',
    company_admin: 'RRHH Empresa',
    employee: 'Comensales',
};

async function loadReport() {
    loading.value = true;
    error.value = null;
    try {
        const res = await api.get(`/superadmin/tenants/${route.params.tenantId}/reports`);
        report.value = res.data;
    } catch (e) {
        error.value = e.message || 'Error al generar el reporte.';
    } finally {
        loading.value = false;
    }
}

const totalOrders = computed(() =>
    Object.values(report.value?.orders_by_status ?? {}).reduce((sum, n) => sum + Number(n), 0),
);

const statusRows = computed(() =>
    Object.entries(STATUS_META).map(([key, meta]) => {
        const count = Number(report.value?.orders_by_status?.[key] ?? 0);
        return {
            key,
            ...meta,
            count,
            pct: totalOrders.value ? Math.round((count / totalOrders.value) * 100) : 0,
        };
    }),
);

const deliveryRate = computed(() => {
    const delivered = Number(report.value?.orders_by_status?.delivered ?? 0);
    const noShow = Number(report.value?.orders_by_status?.no_show ?? 0);
    const closed = delivered + noShow;
    return closed ? Math.round((delivered / closed) * 100) : null;
});

const maxDaily = computed(() =>
    Math.max(1, ...(report.value?.daily_orders ?? []).map((d) => d.total)),
);

const copayFormatted = computed(() =>
    new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(report.value?.copay_total_clp ?? 0),
);

const roleRows = computed(() =>
    Object.entries(report.value?.users_by_role ?? {})
        .filter(([role]) => role !== 'super_admin')
        .map(([role, count]) => ({ role, label: ROLE_LABELS[role] ?? role, count: Number(count) })),
);

function shortDate(iso) {
    const [, month, day] = iso.split('-');
    return `${day}/${month}`;
}

onMounted(loadReport);
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl text-ink tracking-tight">Reportes de la Concesionaria</h1>
            <p class="text-sm text-muted mt-1">
                Indicadores operativos individuales: pedidos, entregas, copagos y catálogo.
            </p>
        </div>

        <div v-if="loading" class="text-center py-16 text-muted">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-ink border-t-transparent mb-3"></div>
            <p class="text-sm">Generando reporte...</p>
        </div>

        <div v-else-if="error" class="rounded-2xl bg-rust/10 border border-rust/30 p-6 text-center text-rust">
            <p class="font-medium">{{ error }}</p>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-rust px-4 py-2 text-xs font-semibold text-white hover:opacity-90"
                @click="loadReport"
            >Reintentar</button>
        </div>

        <template v-else-if="report">
            <!-- KPIs del Reporte -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                    <div class="text-xs font-medium text-faint uppercase tracking-wider mb-2">Pedidos Históricos</div>
                    <div class="text-3xl font-serif font-bold text-ink">{{ totalOrders }}</div>
                    <div class="text-[12px] text-muted mt-1">Todas las órdenes registradas</div>
                </div>
                <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                    <div class="text-xs font-medium text-faint uppercase tracking-wider mb-2">Tasa de Entrega</div>
                    <div class="text-3xl font-serif font-bold" :class="deliveryRate === null ? 'text-muted' : deliveryRate >= 90 ? 'text-emerald-600' : 'text-amber-600'">
                        {{ deliveryRate === null ? '—' : `${deliveryRate}%` }}
                    </div>
                    <div class="text-[12px] text-muted mt-1">Entregados vs. no retirados</div>
                </div>
                <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                    <div class="text-xs font-medium text-faint uppercase tracking-wider mb-2">Copagos Acumulados</div>
                    <div class="text-2xl font-serif font-bold text-ink">{{ copayFormatted }}</div>
                    <div class="text-[12px] text-muted mt-1">Pedidos confirmados y entregados</div>
                </div>
                <div class="rounded-2xl border border-card-line bg-white p-5 shadow-xs">
                    <div class="text-xs font-medium text-faint uppercase tracking-wider mb-2">Catálogo</div>
                    <div class="text-3xl font-serif font-bold text-ink">
                        {{ report.catalog.dishes }} <span class="text-sm font-sans font-normal text-muted">platos</span>
                    </div>
                    <div class="text-[12px] text-muted mt-1">
                        {{ report.catalog.published_menus }} / {{ report.catalog.menus }} menús publicados
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pedidos últimos 30 días -->
                <div class="rounded-2xl border border-card-line bg-white p-6 shadow-xs">
                    <h2 class="text-sm font-bold text-ink uppercase tracking-wider mb-4">Pedidos · Últimos 30 días</h2>
                    <div v-if="report.daily_orders.length" class="flex items-end gap-1 h-40">
                        <div
                            v-for="day in report.daily_orders"
                            :key="day.date"
                            class="flex-1 group relative flex flex-col justify-end h-full"
                        >
                            <div
                                class="w-full rounded-t-sm bg-emerald-500/70 group-hover:bg-emerald-600 transition-colors min-h-[3px]"
                                :style="{ height: `${Math.max(4, (day.total / maxDaily) * 100)}%` }"
                            ></div>
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 hidden group-hover:block bg-ink text-white text-[10px] rounded-md px-2 py-1 whitespace-nowrap z-10">
                                {{ shortDate(day.date) }}: {{ day.total }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-faint italic py-10 text-center">
                        Sin pedidos registrados en los últimos 30 días.
                    </p>
                </div>

                <!-- Desglose por estado -->
                <div class="rounded-2xl border border-card-line bg-white p-6 shadow-xs">
                    <h2 class="text-sm font-bold text-ink uppercase tracking-wider mb-4">Pedidos por Estado</h2>
                    <div class="space-y-4">
                        <div v-for="row in statusRows" :key="row.key">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-ink">{{ row.label }}</span>
                                <span class="text-muted">{{ row.count }} ({{ row.pct }}%)</span>
                            </div>
                            <div class="h-2 rounded-full bg-paper overflow-hidden">
                                <div class="h-full rounded-full transition-all" :class="row.bar" :style="{ width: `${row.pct}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top empresas -->
                <div class="rounded-2xl border border-card-line bg-white p-6 shadow-xs">
                    <h2 class="text-sm font-bold text-ink uppercase tracking-wider mb-4">Top Empresas por Pedidos</h2>
                    <div v-if="report.top_companies.length" class="space-y-3">
                        <div
                            v-for="(company, idx) in report.top_companies"
                            :key="company.id"
                            class="flex items-center gap-3"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-paper border border-line text-xs font-bold text-muted">
                                {{ idx + 1 }}
                            </span>
                            <span class="flex-1 text-sm font-medium text-ink truncate">{{ company.name }}</span>
                            <span class="text-xs font-semibold text-muted">{{ company.orders_count }} pedidos</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-faint italic py-6 text-center">Sin empresas clientes registradas.</p>
                </div>

                <!-- Distribución de usuarios -->
                <div class="rounded-2xl border border-card-line bg-white p-6 shadow-xs">
                    <h2 class="text-sm font-bold text-ink uppercase tracking-wider mb-4">Usuarios por Rol</h2>
                    <div v-if="roleRows.length" class="grid grid-cols-2 gap-3">
                        <div
                            v-for="row in roleRows"
                            :key="row.role"
                            class="rounded-xl border border-line bg-paper/40 px-4 py-3"
                        >
                            <div class="text-2xl font-serif font-bold text-ink">{{ row.count }}</div>
                            <div class="text-xs text-muted mt-0.5">{{ row.label }}</div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-faint italic py-6 text-center">Sin usuarios registrados.</p>
                </div>
            </div>
        </template>
    </div>
</template>
