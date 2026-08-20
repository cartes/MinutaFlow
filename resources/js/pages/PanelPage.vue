<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { clp, longDate, toIsoDate } from '../utils/format';
import ProgressBar from '../components/ProgressBar.vue';

const today = toIsoDate(new Date());
const loading = ref(true);
const menu = ref(null);   // detalle del menú de hoy (items con remaining_quota)
const orders = ref([]);   // pedidos de hoy

const ACTIVE = ['confirmed', 'delivered', 'no_show'];
const barColors = ['bg-gold', 'bg-tan', 'bg-green-mid', 'bg-rust'];

onMounted(async () => {
    try {
        const [menusPage, ordersPage] = await Promise.all([
            api.get('/menus', { from: today, to: today, per_page: 10 }),
            api.get('/orders', { date: today, per_page: 500 }),
        ]);
        orders.value = ordersPage.data ?? [];

        const todayMenu =
            (menusPage.data ?? []).find((m) => m.is_published) ?? (menusPage.data ?? [])[0];
        if (todayMenu) {
            menu.value = await api.get(`/menus/${todayMenu.id}`);
        }
    } finally {
        loading.value = false;
    }
});

const activeOrders = computed(() => orders.value.filter((o) => ACTIVE.includes(o.status)));
const kpis = computed(() => {
    const count = (status) => orders.value.filter((o) => o.status === status).length;
    const active = activeOrders.value.length;
    return [
        { label: 'Pedidos confirmados', value: active, pct: totalQuota.value ? (active / totalQuota.value) * 100 : 0, color: 'bg-green' },
        { label: 'Entregados', value: count('delivered'), pct: active ? (count('delivered') / active) * 100 : 0, color: 'bg-green-mid' },
        { label: 'Cancelados en plazo', value: count('cancelled'), pct: orders.value.length ? (count('cancelled') / orders.value.length) * 100 : 0, color: 'bg-tan' },
        { label: 'No retirados', value: count('no_show'), pct: active ? (count('no_show') / active) * 100 : 0, color: 'bg-rust' },
    ];
});

const totalQuota = computed(() =>
    (menu.value?.items ?? []).reduce((sum, item) => sum + (item.max_quota ?? 0), 0),
);

const avgCopay = computed(() => {
    const list = activeOrders.value;
    if (!list.length) return 0;
    return Math.round(list.reduce((sum, o) => sum + (o.copay_amount_clp ?? 0), 0) / list.length);
});

const noShowPct = computed(() => {
    const active = activeOrders.value.length;
    if (!active) return '0%';
    const pct = (kpis.value[3].value / active) * 100;
    return `${pct.toLocaleString('es-CL', { maximumFractionDigits: 1 })}%`;
});

const demand = computed(() =>
    (menu.value?.items ?? []).map((item, index) => {
        const used = activeOrders.value.filter((o) => o.menu_item_id === item.id).length;
        const quota = item.max_quota;
        return {
            id: item.id,
            label: `${item.option_label ?? `Opción ${index + 1}`} · ${item.dish?.name ?? '—'}`,
            category: item.dish?.category ?? '',
            used,
            quota,
            pct: quota ? (used / quota) * 100 : 0,
            soldOut: item.is_sold_out,
            color: item.is_sold_out ? 'bg-rust' : barColors[index % barColors.length],
        };
    }),
);

const branches = computed(() => {
    const groups = new Map();
    for (const order of activeOrders.value) {
        const name = order.branch?.name ?? 'Sin sucursal';
        const group = groups.get(name) ?? { name, company: order.company?.name ?? '', total: 0, delivered: 0 };
        group.total += 1;
        if (order.status === 'delivered') group.delivered += 1;
        groups.set(name, group);
    }
    return [...groups.values()]
        .map((g) => ({
            ...g,
            status: g.total === 0 ? 'sin pedidos' : g.delivered === g.total ? 'entregado' : g.delivered > 0 ? 'en ruta' : 'preparando',
        }))
        .sort((a, b) => b.total - a.total);
});

function exportPackingList() {
    const lines = [['Sucursal', 'Empresa', 'Opción', 'Plato', 'Cantidad']];
    const groups = new Map();
    for (const order of orders.value.filter((o) => o.status === 'confirmed')) {
        const key = `${order.branch?.name ?? '—'}|${order.menu_item_id}`;
        const row = groups.get(key) ?? {
            branch: order.branch?.name ?? '—',
            company: order.company?.name ?? '—',
            option: order.menu_item?.option_label ?? '—',
            dish: order.menu_item?.dish?.name ?? '—',
            qty: 0,
        };
        row.qty += 1;
        groups.set(key, row);
    }
    for (const row of groups.values()) {
        lines.push([row.branch, row.company, row.option, row.dish, row.qty]);
    }
    const csv = lines.map((cols) => cols.map((c) => `"${String(c).replaceAll('"', '""')}"`).join(';')).join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    link.download = `packing-list-${today}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}
</script>

<template>
    <div>
        <Teleport to="#sidebar-extra" defer>
            <div>
                <div class="mb-2.5 text-xs tracking-wider text-sand-muted uppercase">Cupo del servicio</div>
                <div class="mb-2.5 flex items-baseline gap-2">
                    <span class="text-3xl tracking-tight tabular-nums">{{ activeOrders.length }}</span>
                    <span class="text-[13px] text-sand-muted">/ {{ totalQuota }}</span>
                </div>
                <ProgressBar
                    :value="totalQuota ? (activeOrders.length / totalQuota) * 100 : 0"
                    track="bg-white/65"
                />
            </div>
            <div>
                <div class="mb-2.5 text-xs tracking-wider text-sand-muted uppercase">Copago promedio</div>
                <div class="text-3xl tracking-tight tabular-nums">{{ clp(avgCopay) }}</div>
            </div>
            <div>
                <div class="mb-2.5 text-xs tracking-wider text-sand-muted uppercase">No retirados (hoy)</div>
                <div class="text-3xl tracking-tight tabular-nums">{{ noShowPct }}</div>
            </div>
        </Teleport>

        <div class="mb-[34px] flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-2.5 text-[13px] tracking-wider text-faint uppercase">{{ longDate(today) }}</div>
                <h1 class="font-serif text-[46px] tracking-tight">Servicio del día</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <div
                    v-if="menu?.is_published"
                    class="inline-flex items-center gap-2 rounded-[10px] bg-green-soft px-3.5 py-[9px] text-[13.5px] text-green"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-green-mid"></span>
                    Menú publicado
                </div>
                <div
                    v-else-if="!loading"
                    class="inline-flex items-center gap-2 rounded-[10px] bg-rust/10 px-3.5 py-[9px] text-[13.5px] text-rust"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-rust"></span>
                    {{ menu ? 'Menú en borrador' : 'Sin menú para hoy' }}
                </div>
                <button
                    type="button"
                    class="rounded-[10px] border border-btn-line bg-white px-4 py-2.5 text-sm hover:bg-paper"
                    @click="exportPackingList"
                >Exportar packing list</button>
                <router-link
                    :to="{ name: 'menus' }"
                    class="rounded-[10px] border border-ink bg-ink px-[18px] py-2.5 text-sm text-white hover:bg-ink-hover"
                >Armar mañana</router-link>
            </div>
        </div>

        <div v-if="loading" class="py-24 text-center text-faint">Cargando el servicio del día…</div>

        <template v-else>
            <div class="mb-7 grid grid-cols-2 gap-4 xl:grid-cols-4">
                <div
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="rounded-2xl border border-card-line bg-white px-6 py-[22px]"
                >
                    <div class="mb-3.5 text-[13px] text-faint">{{ kpi.label }}</div>
                    <div class="mb-3.5 text-4xl tracking-tight tabular-nums">{{ kpi.value }}</div>
                    <ProgressBar :value="kpi.pct" :color="kpi.color" />
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
                <div class="rounded-[18px] border border-card-line bg-white px-7 pt-[26px] pb-[30px]">
                    <div class="mb-[26px] flex items-baseline justify-between">
                        <h2 class="text-[17px] font-semibold tracking-tight">Demanda por opción</h2>
                        <span class="text-[13px] text-faint">cupo usado</span>
                    </div>

                    <div v-if="!demand.length" class="py-8 text-center text-sm text-faint">
                        No hay menú con opciones para hoy.
                        <router-link :to="{ name: 'menus' }" class="text-green hover:text-green-deep">Armar el menú →</router-link>
                    </div>

                    <div class="grid gap-6">
                        <div v-for="row in demand" :key="row.id">
                            <div class="mb-[9px] flex items-baseline justify-between">
                                <div class="flex items-baseline gap-2.5">
                                    <span class="text-[15px] font-medium">{{ row.label }}</span>
                                    <span class="text-[12.5px] text-faint">{{ row.category }}</span>
                                </div>
                                <span class="text-sm text-muted tabular-nums">
                                    {{ row.used }} / {{ row.quota ?? '∞' }}
                                </span>
                            </div>
                            <ProgressBar :value="row.pct" :color="row.color" height="h-1.5" />
                            <div v-if="row.soldOut" class="mt-2 text-[12.5px] text-rust">Cupo agotado</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[18px] border border-card-line bg-white px-7 pt-[26px] pb-3">
                    <div class="mb-5 flex items-baseline justify-between">
                        <h2 class="text-[17px] font-semibold tracking-tight">Despacho por sucursal</h2>
                        <span class="text-[13px] text-faint">hoy</span>
                    </div>
                    <div v-if="!branches.length" class="py-8 text-center text-sm text-faint">
                        Aún no hay pedidos para hoy.
                    </div>
                    <div class="grid">
                        <div
                            v-for="(branch, index) in branches"
                            :key="branch.name"
                            class="flex items-center justify-between py-[15px]"
                            :class="index < branches.length - 1 ? 'border-b border-row-line' : ''"
                        >
                            <div>
                                <div class="mb-[3px] text-[14.5px]">
                                    {{ branch.company ? `${branch.company} · ` : '' }}{{ branch.name }}
                                </div>
                                <div class="text-[12.5px] text-faint">{{ branch.delivered }} entregados</div>
                            </div>
                            <div class="text-right">
                                <div class="text-base tabular-nums">{{ branch.total }}</div>
                                <div
                                    class="text-xs"
                                    :class="branch.status === 'preparando' ? 'text-rust' : branch.status === 'sin pedidos' ? 'text-faint' : 'text-green-mid'"
                                >{{ branch.status }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
