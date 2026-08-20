<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '../api';
import { clp, toIsoDate, parseIsoDate, capitalize } from '../utils/format';
import ProgressBar from '../components/ProgressBar.vue';

/* ------------------------------ Semana ------------------------------ */
const weekStart = ref(mondayOf(new Date()));
const weekMenus = ref({});      // iso date → menú (con items.dish, del index)
const selectedDate = ref(null);
const loadingWeek = ref(true);
const error = ref(null);

function mondayOf(date) {
    const d = new Date(date);
    const day = (d.getDay() + 6) % 7; // lunes = 0
    d.setDate(d.getDate() - day);
    d.setHours(0, 0, 0, 0);
    return d;
}

const weekDays = computed(() =>
    [0, 1, 2, 3, 4].map((offset) => {
        const d = new Date(weekStart.value);
        d.setDate(d.getDate() + offset);
        const iso = toIsoDate(d);
        const menu = weekMenus.value[iso];
        return {
            iso,
            label: capitalize(d.toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric' })),
            status: !menu ? 'Vacío' : menu.is_published ? 'Publicado' : 'Borrador',
        };
    }),
);

const weekLabel = computed(() => {
    const end = new Date(weekStart.value);
    end.setDate(end.getDate() + 4);
    return `Semana del ${weekStart.value.getDate()} al ${end.getDate()}`;
});

async function loadWeek() {
    loadingWeek.value = true;
    error.value = null;
    try {
        const from = toIsoDate(weekStart.value);
        const end = new Date(weekStart.value);
        end.setDate(end.getDate() + 4);
        const page = await api.get('/menus', { from, to: toIsoDate(end), per_page: 50 });
        weekMenus.value = Object.fromEntries(
            (page.data ?? []).map((m) => [String(m.menu_date).slice(0, 10), m]),
        );
        if (!selectedDate.value || !weekDays.value.some((d) => d.iso === selectedDate.value)) {
            // Por defecto: mañana si cae en la semana visible, si no el lunes.
            const tomorrow = toIsoDate(new Date(Date.now() + 86400000));
            selectedDate.value = weekDays.value.some((d) => d.iso === tomorrow)
                ? tomorrow
                : weekDays.value[0].iso;
        }
    } catch (e) {
        error.value = e.message;
    } finally {
        loadingWeek.value = false;
    }
}

function moveWeek(deltaDays) {
    const d = new Date(weekStart.value);
    d.setDate(d.getDate() + deltaDays);
    weekStart.value = d;
    selectedDate.value = null;
    loadWeek();
}

onMounted(loadWeek);

/* ------------------------------ Menú seleccionado ------------------------------ */
const menu = computed(() => weekMenus.value[selectedDate.value] ?? null);
const items = computed(() =>
    [...(menu.value?.items ?? [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)),
);

const headerDate = computed(() => {
    if (!selectedDate.value) return '';
    const d = parseIsoDate(selectedDate.value);
    return capitalize(d.toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long' }));
});

const totalQuota = computed(() =>
    items.value.reduce((sum, item) => sum + (item.max_quota ?? 0), 0),
);

const coverage = computed(() => {
    const groups = new Map();
    for (const item of items.value) {
        const key = item.dish?.category ?? 'Otros';
        groups.set(key, (groups.get(key) ?? 0) + 1);
    }
    const max = Math.max(1, ...groups.values());
    const colors = ['bg-gold', 'bg-green-mid', 'bg-tan', 'bg-rust'];
    return [...groups.entries()].map(([label, count], index) => ({
        label,
        count,
        pct: (count / max) * 100,
        color: colors[index % colors.length],
    }));
});

const checklist = computed(() => {
    const list = [];
    list.push({
        ok: items.value.length > 0,
        text: items.value.length
            ? `Cupo total ${totalQuota.value} · ${items.value.length} ${items.value.length === 1 ? 'opción' : 'opciones'}`
            : 'El menú aún no tiene opciones',
    });
    const undeclared = items.value.filter((i) => i.dish?.allergens === null);
    list.push({
        ok: undeclared.length === 0,
        text: undeclared.length
            ? `${undeclared.length} plato(s) sin alérgenos declarados`
            : 'Todas las opciones tienen alérgenos declarados',
    });
    const hasVegan = items.value.some((i) =>
        (i.dish?.dietary_tags ?? []).some((t) => ['vegan', 'vegana', 'vegetarian'].includes(String(t).toLowerCase())),
    );
    list.push({
        ok: hasVegan,
        text: hasVegan ? 'Incluye alternativa vegana/vegetariana' : 'Sin alternativa vegana activa',
    });
    return list;
});

/* ------------------------------ Acciones ------------------------------ */
const busy = ref(false);

async function run(action) {
    busy.value = true;
    error.value = null;
    try {
        await action();
        await loadWeek();
    } catch (e) {
        error.value = e.message;
    } finally {
        busy.value = false;
    }
}

const createDraft = () => run(() => api.post('/menus', { menu_date: selectedDate.value }));
const publish = () => run(() => api.post(`/menus/${menu.value.id}/publish`));
const unpublish = () => run(() => api.post(`/menus/${menu.value.id}/unpublish`));
const removeItem = (item) => run(() => api.delete(`/menu-items/${item.id}`));

function toggleItem(item) {
    run(() => api.patch(`/menu-items/${item.id}`, { is_available: !item.is_available }));
}

function saveItemField(item, field, rawValue) {
    const value = rawValue === '' ? null : Number(rawValue);
    if (value === item[field]) return;
    run(() => api.patch(`/menu-items/${item.id}`, { [field]: value }));
}

const duplicateSource = computed(() => {
    const candidates = Object.entries(weekMenus.value)
        .filter(([iso, m]) => iso !== selectedDate.value && (m.items?.length ?? 0) > 0)
        .sort(([a], [b]) => (a < b ? 1 : -1));
    const before = candidates.find(([iso]) => iso < selectedDate.value);
    return (before ?? candidates[0])?.[1] ?? null;
});

function duplicateFromSource() {
    const source = duplicateSource.value;
    if (!source) return;
    run(async () => {
        let target = menu.value;
        if (!target) {
            target = await api.post('/menus', { menu_date: selectedDate.value });
        }
        for (const [index, item] of source.items.entries()) {
            await api.post(`/menus/${target.id}/items`, {
                dish_id: item.dish_id,
                option_label: item.option_label,
                max_quota: item.max_quota,
                price_extra_clp: item.price_extra_clp,
                is_available: item.is_available,
                sort_order: (menu.value?.items?.length ?? 0) + index,
            });
        }
    });
}

/* ------------------------------ Selector de platos ------------------------------ */
const showPicker = ref(false);
const dishSearch = ref('');
const dishes = ref([]);
const loadingDishes = ref(false);

watch(showPicker, (open) => open && searchDishes());

let searchTimer = null;
watch(dishSearch, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(searchDishes, 300);
});

async function searchDishes() {
    loadingDishes.value = true;
    try {
        const page = await api.get('/dishes', { active: 1, search: dishSearch.value, per_page: 50 });
        dishes.value = page.data ?? [];
    } finally {
        loadingDishes.value = false;
    }
}

function addDish(dish) {
    showPicker.value = false;
    run(async () => {
        let target = menu.value;
        if (!target) {
            target = await api.post('/menus', { menu_date: selectedDate.value });
        }
        const position = (target.items?.length ?? 0) + 1;
        await api.post(`/menus/${target.id}/items`, {
            dish_id: dish.id,
            option_label: `Opción ${position}`,
            max_quota: 50,
            price_extra_clp: 0,
            is_available: true,
            sort_order: position - 1,
        });
    });
}

function dishMeta(dish) {
    const parts = [];
    if (dish?.category) parts.push(dish.category);
    if (dish?.calories_kcal) parts.push(`${dish.calories_kcal} kcal`);
    if (dish?.allergens?.length) parts.push(`alérgenos: ${dish.allergens.join(', ')}`);
    else if (dish?.allergens) parts.push('sin alérgenos declarados');
    return parts.join(' · ');
}
</script>

<template>
    <div>
        <Teleport to="#sidebar-extra" defer>
            <div>
                <div class="mb-3.5 flex items-center justify-between">
                    <span class="text-xs tracking-wider text-sand-muted uppercase">{{ weekLabel }}</span>
                    <span class="flex gap-1">
                        <button type="button" class="rounded px-1.5 text-sand-muted hover:bg-white/50" @click="moveWeek(-7)">‹</button>
                        <button type="button" class="rounded px-1.5 text-sand-muted hover:bg-white/50" @click="moveWeek(7)">›</button>
                    </span>
                </div>
                <div class="grid gap-[3px]">
                    <button
                        v-for="day in weekDays"
                        :key="day.iso"
                        type="button"
                        class="flex items-center justify-between rounded-[10px] px-3 py-2.5 text-left text-sm"
                        :class="day.iso === selectedDate ? 'bg-white font-medium' : 'text-sand-text hover:bg-white/50'"
                        @click="selectedDate = day.iso"
                    >
                        <span>{{ day.label }}</span>
                        <span
                            class="text-xs"
                            :class="day.status === 'Publicado' ? 'text-green-mid' : 'text-sand-muted'"
                        >{{ day.status }}</span>
                    </button>
                </div>
            </div>

            <div>
                <div class="mb-3 text-xs tracking-wider text-sand-muted uppercase">Alcance</div>
                <div class="rounded-xl bg-white/60 px-3.5 pt-3.5 pb-4">
                    <div class="mb-1 text-sm">{{ menu?.company?.name ?? 'Menú general' }}</div>
                    <div class="text-[12.5px] leading-normal text-sand-soft">
                        {{ menu?.company ? 'Menú exclusivo para esta empresa cliente.' : 'Visible para todas las empresas cliente del tenant.' }}
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-2.5 text-xs tracking-wider text-sand-muted uppercase">Cupo total definido</div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl tracking-tight tabular-nums">{{ totalQuota }}</span>
                    <span class="text-[13px] text-sand-muted">porciones</span>
                </div>
            </div>
        </Teleport>

        <div class="mb-[34px] flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-2.5 text-[13px] tracking-wider text-faint uppercase">
                    {{ menu ? (menu.is_published ? 'Publicado' : 'Borrador') : 'Sin menú' }} · {{ headerDate }}
                </div>
                <h1 class="font-serif text-[46px] tracking-tight">Armar el menú</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <button
                    v-if="duplicateSource"
                    type="button"
                    :disabled="busy"
                    class="rounded-[10px] border border-btn-line bg-white px-4 py-2.5 text-sm hover:bg-paper disabled:opacity-60"
                    @click="duplicateFromSource"
                >Duplicar otro día</button>
                <button
                    v-if="menu && !menu.is_published"
                    type="button"
                    :disabled="busy || !items.length"
                    class="rounded-[10px] border border-green bg-green px-[18px] py-2.5 text-sm text-white hover:bg-green-hover disabled:opacity-60"
                    @click="publish"
                >Publicar menú</button>
                <button
                    v-if="menu?.is_published"
                    type="button"
                    :disabled="busy"
                    class="rounded-[10px] border border-btn-line bg-white px-[18px] py-2.5 text-sm hover:bg-paper disabled:opacity-60"
                    @click="unpublish"
                >Despublicar</button>
            </div>
        </div>

        <p v-if="error" class="mb-5 rounded-[10px] bg-rust/10 px-4 py-3 text-[13.5px] text-rust">{{ error }}</p>

        <div v-if="loadingWeek" class="py-24 text-center text-faint">Cargando la semana…</div>

        <div v-else class="grid items-start gap-5 xl:grid-cols-[1fr_320px]">
            <div class="grid gap-3.5">
                <div
                    v-if="!menu"
                    class="rounded-2xl border border-dashed border-dash-line px-6 py-14 text-center"
                >
                    <p class="mb-4 text-[15px] text-muted">No hay menú para este día todavía.</p>
                    <button
                        type="button"
                        :disabled="busy"
                        class="rounded-[10px] border border-ink bg-ink px-5 py-2.5 text-sm text-white hover:bg-ink-hover disabled:opacity-60"
                        @click="createDraft"
                    >Crear borrador</button>
                </div>

                <div
                    v-for="item in items"
                    :key="item.id"
                    class="grid grid-cols-[1fr_96px_108px_92px] items-center gap-[22px] rounded-2xl border border-card-line bg-white px-6 py-[22px]"
                    :class="{ 'opacity-60': !item.is_available }"
                >
                    <div>
                        <div class="mb-1.5 flex items-center gap-2.5">
                            <span class="rounded-full bg-paper px-[9px] py-[3px] text-xs text-faint">{{ item.option_label }}</span>
                            <span class="text-base font-semibold tracking-tight">{{ item.dish?.name ?? '—' }}</span>
                            <button
                                type="button"
                                class="ml-1 text-xs text-faint hover:text-rust"
                                title="Quitar opción"
                                @click="removeItem(item)"
                            >✕</button>
                        </div>
                        <div class="text-[13px] text-faint">{{ dishMeta(item.dish) }}</div>
                    </div>
                    <div>
                        <div class="mb-[5px] text-xs text-faint">Cupo</div>
                        <input
                            type="number"
                            min="1"
                            :value="item.max_quota"
                            class="w-full rounded-lg border border-transparent bg-transparent text-lg tabular-nums outline-none hover:border-btn-line focus:border-green"
                            @change="saveItemField(item, 'max_quota', $event.target.value)"
                        />
                    </div>
                    <div>
                        <div class="mb-[5px] text-xs text-faint">Copago extra</div>
                        <input
                            type="number"
                            min="0"
                            step="100"
                            :value="item.price_extra_clp"
                            class="w-full rounded-lg border border-transparent bg-transparent text-lg tabular-nums outline-none hover:border-btn-line focus:border-green"
                            @change="saveItemField(item, 'price_extra_clp', $event.target.value)"
                        />
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="flex h-6 w-[42px] items-center rounded-full px-[3px] transition-colors"
                            :class="item.is_available ? 'justify-end bg-green' : 'justify-start bg-line'"
                            :title="item.is_available ? 'Deshabilitar opción' : 'Habilitar opción'"
                            @click="toggleItem(item)"
                        >
                            <span class="h-[18px] w-[18px] rounded-full bg-white"></span>
                        </button>
                    </div>
                </div>

                <button
                    v-if="menu"
                    type="button"
                    class="rounded-2xl border border-dashed border-dash-line px-6 py-5 text-left text-[14.5px] text-faint hover:bg-paper hover:text-muted"
                    @click="showPicker = true"
                >+ Agregar opción desde el catálogo de platos</button>
            </div>

            <div class="grid gap-3.5">
                <div class="rounded-2xl border border-card-line bg-white px-6 py-[22px]">
                    <h2 class="mb-[18px] text-[15px] font-semibold">Cobertura dietaria</h2>
                    <div v-if="!coverage.length" class="text-[13.5px] text-faint">Agrega opciones para ver la cobertura.</div>
                    <div class="grid gap-3.5">
                        <div v-for="row in coverage" :key="row.label">
                            <div class="mb-[7px] flex justify-between text-[13.5px]">
                                <span class="text-muted">{{ row.label }}</span>
                                <span class="text-faint">{{ row.count }} {{ row.count === 1 ? 'opción' : 'opciones' }}</span>
                            </div>
                            <ProgressBar :value="row.pct" :color="row.color" />
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-card-line bg-white px-6 py-[22px]">
                    <h2 class="mb-4 text-[15px] font-semibold">Antes de publicar</h2>
                    <div class="grid gap-3 text-[13.5px] leading-normal">
                        <div v-for="(check, index) in checklist" :key="index" class="flex gap-2.5">
                            <span :class="check.ok ? 'text-green-mid' : 'text-rust'">{{ check.ok ? '✓' : '!' }}</span>
                            <span class="text-muted">{{ check.text }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-sand px-6 py-[22px]">
                    <div class="mb-2.5 text-xs tracking-wider text-sand-muted uppercase">Corte de pedidos</div>
                    <div class="mb-2 font-serif text-[34px] leading-none">Día anterior</div>
                    <div class="text-[13px] leading-normal text-sand-soft">
                        Según el horario de corte configurado para cada empresa cliente.
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de platos -->
        <div
            v-if="showPicker"
            class="fixed inset-0 z-50 flex items-start justify-center bg-ink/30 p-6 pt-[10vh]"
            @click.self="showPicker = false"
        >
            <div class="w-full max-w-[560px] rounded-[18px] border border-card-line bg-white shadow-[0_24px_60px_-30px_rgba(60,50,30,0.5)]">
                <div class="border-b border-row-line p-4">
                    <input
                        v-model="dishSearch"
                        type="search"
                        placeholder="Buscar en el catálogo de platos…"
                        class="w-full rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green"
                        autofocus
                    />
                </div>
                <div class="max-h-[50vh] overflow-y-auto p-2">
                    <div v-if="loadingDishes" class="py-8 text-center text-sm text-faint">Buscando…</div>
                    <div v-else-if="!dishes.length" class="py-8 text-center text-sm text-faint">Sin resultados en el catálogo.</div>
                    <button
                        v-for="dish in dishes"
                        :key="dish.id"
                        type="button"
                        class="flex w-full items-baseline justify-between gap-4 rounded-xl px-3.5 py-3 text-left hover:bg-paper"
                        @click="addDish(dish)"
                    >
                        <span>
                            <span class="block text-[15px] font-medium">{{ dish.name }}</span>
                            <span class="block text-[12.5px] text-faint">{{ dishMeta(dish) }}</span>
                        </span>
                        <span v-if="dish.raw_cost_clp" class="text-[13px] whitespace-nowrap text-faint tabular-nums">
                            costo {{ clp(dish.raw_cost_clp) }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
