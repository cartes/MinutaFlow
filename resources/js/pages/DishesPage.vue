<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '../api';
import { clp } from '../utils/format';

const dishes = ref([]);
const loading = ref(true);
const error = ref(null);
const search = ref('');
const category = ref('');

async function load() {
    loading.value = true;
    error.value = null;
    try {
        const page = await api.get('/dishes', {
            search: search.value,
            category: category.value,
            per_page: 100,
        });
        dishes.value = page.data ?? [];
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

let timer = null;
watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(load, 300);
});
watch(category, load);
onMounted(load);

const categories = computed(() =>
    [...new Set(dishes.value.map((d) => d.category).filter(Boolean))].sort(),
);

async function toggleActive(dish) {
    try {
        const updated = await api.patch(`/dishes/${dish.id}`, { is_active: !dish.is_active });
        Object.assign(dish, updated);
    } catch (e) {
        error.value = e.message;
    }
}

/* ------------------------------ Nuevo plato ------------------------------ */
const showForm = ref(false);
const saving = ref(false);
const form = ref(emptyForm());

function emptyForm() {
    return { name: '', category: '', description: '', calories_kcal: '', raw_cost_clp: '', allergens: '', dietary_tags: '' };
}

const csv = (text) =>
    text.split(',').map((s) => s.trim()).filter(Boolean);

async function saveDish() {
    saving.value = true;
    error.value = null;
    try {
        await api.post('/dishes', {
            name: form.value.name,
            category: form.value.category || null,
            description: form.value.description || null,
            calories_kcal: form.value.calories_kcal ? Number(form.value.calories_kcal) : null,
            raw_cost_clp: form.value.raw_cost_clp ? Number(form.value.raw_cost_clp) : null,
            allergens: csv(form.value.allergens),
            dietary_tags: csv(form.value.dietary_tags),
            is_active: true,
        });
        showForm.value = false;
        form.value = emptyForm();
        await load();
    } catch (e) {
        error.value = e.errors ? Object.values(e.errors).flat().join(' ') : e.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div>
        <div class="mb-[34px] flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-2.5 text-[13px] tracking-wider text-faint uppercase">Catálogo del catering</div>
                <h1 class="font-serif text-[46px] tracking-tight">Platos</h1>
            </div>
            <button
                type="button"
                class="rounded-[10px] border border-ink bg-ink px-[18px] py-2.5 text-sm text-white hover:bg-ink-hover"
                @click="showForm = true"
            >Nuevo plato</button>
        </div>

        <p v-if="error" class="mb-5 rounded-[10px] bg-rust/10 px-4 py-3 text-[13.5px] text-rust">{{ error }}</p>

        <div class="mb-6 flex flex-wrap items-center gap-2.5">
            <input
                v-model="search"
                type="search"
                placeholder="Buscar plato…"
                class="w-64 rounded-[10px] border border-btn-line bg-white px-3.5 py-2.5 text-sm outline-none focus:border-green"
            />
            <button
                type="button"
                class="rounded-full border px-3.5 py-1.5 text-[13px]"
                :class="category === '' ? 'border-green bg-green-soft text-green' : 'border-btn-line bg-white text-muted hover:bg-paper'"
                @click="category = ''"
            >Todos</button>
            <button
                v-for="cat in categories"
                :key="cat"
                type="button"
                class="rounded-full border px-3.5 py-1.5 text-[13px]"
                :class="category === cat ? 'border-green bg-green-soft text-green' : 'border-btn-line bg-white text-muted hover:bg-paper'"
                @click="category = cat"
            >{{ cat }}</button>
        </div>

        <div v-if="loading" class="py-24 text-center text-faint">Cargando catálogo…</div>
        <div v-else-if="!dishes.length" class="rounded-2xl border border-dashed border-dash-line py-16 text-center text-muted">
            No hay platos en el catálogo todavía.
        </div>

        <div v-else class="grid gap-3.5 md:grid-cols-2 2xl:grid-cols-3">
            <div
                v-for="dish in dishes"
                :key="dish.id"
                class="rounded-2xl border border-card-line bg-white px-6 py-[22px]"
                :class="{ 'opacity-60': !dish.is_active }"
            >
                <div class="mb-1.5 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-base font-semibold tracking-tight">{{ dish.name }}</span>
                        <span v-if="dish.category" class="rounded-full bg-paper px-[9px] py-[3px] text-xs text-faint">{{ dish.category }}</span>
                    </div>
                    <button
                        type="button"
                        class="flex h-6 w-[42px] shrink-0 items-center rounded-full px-[3px] transition-colors"
                        :class="dish.is_active ? 'justify-end bg-green' : 'justify-start bg-line'"
                        :title="dish.is_active ? 'Desactivar' : 'Activar'"
                        @click="toggleActive(dish)"
                    >
                        <span class="h-[18px] w-[18px] rounded-full bg-white"></span>
                    </button>
                </div>
                <p v-if="dish.description" class="mb-3 text-[13.5px] leading-normal text-muted">{{ dish.description }}</p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-faint">
                    <span v-if="dish.calories_kcal">{{ dish.calories_kcal }} kcal</span>
                    <span v-if="dish.raw_cost_clp" class="tabular-nums">costo {{ clp(dish.raw_cost_clp) }}</span>
                    <span v-if="dish.allergens?.length">alérgenos: {{ dish.allergens.join(', ') }}</span>
                    <span v-else>sin alérgenos declarados</span>
                </div>
                <div v-if="dish.dietary_tags?.length" class="mt-2.5 flex flex-wrap gap-1.5">
                    <span
                        v-for="tag in dish.dietary_tags"
                        :key="tag"
                        class="rounded-full bg-green-soft px-2.5 py-0.5 text-xs text-green"
                    >{{ tag }}</span>
                </div>
            </div>
        </div>

        <!-- Formulario nuevo plato -->
        <div
            v-if="showForm"
            class="fixed inset-0 z-50 flex items-start justify-center bg-ink/30 p-6 pt-[8vh]"
            @click.self="showForm = false"
        >
            <form
                class="w-full max-w-[520px] rounded-[18px] border border-card-line bg-white p-7 shadow-[0_24px_60px_-30px_rgba(60,50,30,0.5)]"
                @submit.prevent="saveDish"
            >
                <h2 class="mb-5 font-serif text-[28px] tracking-tight">Nuevo plato</h2>
                <div class="grid gap-3.5">
                    <label class="grid gap-1.5">
                        <span class="text-[13px] text-muted">Nombre *</span>
                        <input v-model="form.name" required class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green" />
                    </label>
                    <div class="grid grid-cols-2 gap-3.5">
                        <label class="grid gap-1.5">
                            <span class="text-[13px] text-muted">Categoría</span>
                            <input v-model="form.category" placeholder="Fondo, Vegana…" class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green" />
                        </label>
                        <label class="grid gap-1.5">
                            <span class="text-[13px] text-muted">Calorías (kcal)</span>
                            <input v-model="form.calories_kcal" type="number" min="0" class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green" />
                        </label>
                    </div>
                    <label class="grid gap-1.5">
                        <span class="text-[13px] text-muted">Descripción</span>
                        <textarea v-model="form.description" rows="2" class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green"></textarea>
                    </label>
                    <div class="grid grid-cols-2 gap-3.5">
                        <label class="grid gap-1.5">
                            <span class="text-[13px] text-muted">Alérgenos (coma)</span>
                            <input v-model="form.allergens" placeholder="gluten, lactosa" class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green" />
                        </label>
                        <label class="grid gap-1.5">
                            <span class="text-[13px] text-muted">Tags dietarios (coma)</span>
                            <input v-model="form.dietary_tags" placeholder="vegan, gluten_free" class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green" />
                        </label>
                    </div>
                    <label class="grid gap-1.5">
                        <span class="text-[13px] text-muted">Costo materia prima (CLP)</span>
                        <input v-model="form.raw_cost_clp" type="number" min="0" step="100" class="rounded-[10px] border border-btn-line px-3.5 py-2.5 text-[15px] outline-none focus:border-green" />
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2.5">
                    <button type="button" class="rounded-[10px] border border-btn-line bg-white px-4 py-2.5 text-sm hover:bg-paper" @click="showForm = false">Cancelar</button>
                    <button type="submit" :disabled="saving" class="rounded-[10px] border border-green bg-green px-[18px] py-2.5 text-sm text-white hover:bg-green-hover disabled:opacity-60">
                        {{ saving ? 'Guardando…' : 'Guardar plato' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
