<script setup>
import { ref, reactive, onMounted, watch } from 'vue';

const locations = [
    {
        id: 'quilicura',
        name: 'Planta Quilicura',
        items: [
            { id: 'fondo', label: 'Fondo del día', targetValue: 184, maxQuota: 225, pct: 82, color: 'bg-gold', badge: 'Alta demanda' },
            { id: 'hipo', label: 'Hipocalórico', targetValue: 92, maxQuota: 225, pct: 41, color: 'bg-tan', badge: 'Equilibrado' },
            { id: 'vegana', label: 'Vegana', targetValue: 37, maxQuota: 225, pct: 17, color: 'bg-green-mid', badge: 'Planta' },
        ],
        cutoff: 'Cierra en 2 h 14 min',
    },
    {
        id: 'las_condes',
        name: 'Sede Las Condes',
        items: [
            { id: 'fondo', label: 'Fondo del día', targetValue: 142, maxQuota: 180, pct: 79, color: 'bg-gold', badge: 'Alta demanda' },
            { id: 'hipo', label: 'Hipocalórico', targetValue: 88, maxQuota: 180, pct: 49, color: 'bg-tan', badge: 'Equilibrado' },
            { id: 'vegana', label: 'Vegana', targetValue: 51, maxQuota: 180, pct: 28, color: 'bg-green-mid', badge: 'Planta' },
        ],
        cutoff: 'Cierra en 1 h 45 min',
    },
    {
        id: 'pudahuel',
        name: 'Centro Pudahuel',
        items: [
            { id: 'fondo', label: 'Fondo del día', targetValue: 115, maxQuota: 150, pct: 76, color: 'bg-gold', badge: 'Alta demanda' },
            { id: 'hipo', label: 'Hipocalórico', targetValue: 48, maxQuota: 150, pct: 32, color: 'bg-tan', badge: 'Equilibrado' },
            { id: 'vegana', label: 'Vegana', targetValue: 24, maxQuota: 150, pct: 16, color: 'bg-green-mid', badge: 'Planta' },
        ],
        cutoff: 'Cierra en 3 h 10 min',
    },
];

const activeLocationIndex = ref(0);
const hoveredIndex = ref(null);
const activeLocation = ref(locations[0]);
const isLocationMenuOpen = ref(false);
const clickFeedback = reactive({});

// Estado numérico animado para cada barra
const displayNumbers = reactive([0, 0, 0]);
const displayPercents = reactive([0, 0, 0]);

// Función de animación con JavaScript (requestAnimationFrame con easing suave)
function animateValues(targetValues, targetPercents, duration = 1200) {
    const startValues = [...displayNumbers];
    const startPercents = [...displayPercents];
    const startTime = performance.now();

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function step(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easedProgress = easeOutCubic(progress);

        targetValues.forEach((target, index) => {
            const start = startValues[index] ?? 0;
            displayNumbers[index] = Math.round(start + (target - start) * easedProgress);
        });

        targetPercents.forEach((target, index) => {
            const start = startPercents[index] ?? 0;
            displayPercents[index] = Math.round(start + (target - start) * easedProgress);
        });

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    }

    requestAnimationFrame(step);
}

function selectLocation(index) {
    activeLocationIndex.value = index;
    activeLocation.value = locations[index];
    isLocationMenuOpen.value = false;

    const targets = activeLocation.value.items.map((i) => i.targetValue);
    const pcts = activeLocation.value.items.map((i) => i.pct);
    animateValues(targets, pcts, 900);
}

// Simular un pedido interactivo al hacer click en la opción
function simulateOrder(index) {
    const item = activeLocation.value.items[index];
    item.targetValue += 1;
    item.pct = Math.min(100, Math.round((item.targetValue / item.maxQuota) * 100));

    // Disparar animación +1 feedback
    clickFeedback[index] = true;
    setTimeout(() => {
        clickFeedback[index] = false;
    }, 800);

    const targets = activeLocation.value.items.map((i) => i.targetValue);
    const pcts = activeLocation.value.items.map((i) => i.pct);
    animateValues(targets, pcts, 400);
}

onMounted(() => {
    // Al cargar la página, las barras inician en 0 y crecen con JavaScript
    setTimeout(() => {
        const targets = activeLocation.value.items.map((i) => i.targetValue);
        const pcts = activeLocation.value.items.map((i) => i.pct);
        animateValues(targets, pcts, 1400);
    }, 150);
});
</script>

<template>
    <div class="rounded-[22px] border border-card-line bg-white px-8 pt-[30px] pb-[34px] shadow-[0_24px_60px_-40px_rgba(60,50,30,0.45)] transition-all relative select-none">
        <!-- Cabecera de la tarjeta con selector de sucursal interactivo -->
        <div class="mb-[26px] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[13px] tracking-wider text-faint uppercase font-medium">Servicio de hoy</span>
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>

            <!-- Selector de Planta/Sucursal Interactivo -->
            <div class="relative">
                <button
                    type="button"
                    class="group flex items-center gap-1.5 rounded-lg bg-cream/70 hover:bg-cream px-2.5 py-1 text-[13px] text-ink transition-colors focus:outline-none border border-line/60 font-medium cursor-pointer"
                    @click="isLocationMenuOpen = !isLocationMenuOpen"
                >
                    <span>{{ activeLocation.name }}</span>
                    <svg
                        class="w-3.5 h-3.5 text-muted transition-transform duration-200"
                        :class="isLocationMenuOpen ? 'rotate-180' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Menú desplegable sucursales -->
                <div
                    v-show="isLocationMenuOpen"
                    class="absolute right-0 mt-1.5 w-48 rounded-xl bg-white border border-line shadow-xl py-1 z-30 text-xs"
                >
                    <button
                        v-for="(loc, idx) in locations"
                        :key="loc.id"
                        type="button"
                        class="w-full text-left px-3.5 py-2 hover:bg-paper flex items-center justify-between transition-colors cursor-pointer"
                        :class="idx === activeLocationIndex ? 'text-green font-semibold bg-green-soft/40' : 'text-muted'"
                        @click="selectLocation(idx)"
                    >
                        <span>{{ loc.name }}</span>
                        <span v-if="idx === activeLocationIndex" class="h-1.5 w-1.5 rounded-full bg-green"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Gráficos de barras interactivas que crecen con JavaScript -->
        <div class="grid gap-[22px]">
            <div
                v-for="(row, index) in activeLocation.items"
                :key="row.id"
                class="group relative rounded-xl p-2 -mx-2 transition-all duration-200 cursor-pointer hover:bg-paper/80"
                @mouseenter="hoveredIndex = index"
                @mouseleave="hoveredIndex = null"
                @click="simulateOrder(index)"
                title="Haz clic para simular un pedido en tiempo real"
            >
                <div class="mb-2 flex items-baseline justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-muted group-hover:text-ink transition-colors">
                            {{ row.label }}
                        </span>
                        <span
                            class="text-[10.5px] px-1.5 py-0.5 rounded-md bg-cream text-faint transition-opacity duration-200"
                            :class="hoveredIndex === index ? 'opacity-100 bg-green-soft text-green font-semibold' : 'opacity-0 sm:opacity-70'"
                        >
                            {{ row.badge }}
                        </span>
                    </div>

                    <!-- Contador con animación numérica y animación de clic +1 -->
                    <div class="flex items-center gap-1.5 relative">
                        <span
                            v-if="clickFeedback[index]"
                            class="absolute -top-4 right-0 text-xs font-bold text-green animate-bounce"
                        >
                            +1 pedido
                        </span>
                        <span class="text-[22px] font-semibold tracking-tight tabular-nums text-ink">
                            {{ displayNumbers[index] }}
                        </span>
                    </div>
                </div>

                <!-- Barra de Progreso con Crecimiento Animado en JavaScript -->
                <div class="w-full bg-cream rounded-full overflow-hidden h-[5px] group-hover:h-[7px] transition-all duration-200 relative">
                    <div
                        class="h-full rounded-full transition-all duration-500 ease-out will-change-[width]"
                        :class="row.color"
                        :style="{ width: `${displayPercents[index]}%` }"
                    ></div>
                </div>

                <!-- Detalle contextual interactivo al pasar el cursor (Hover Tooltip) -->
                <div
                    class="mt-1.5 flex items-center justify-between text-[11px] text-faint transition-all duration-200"
                    :class="hoveredIndex === index ? 'opacity-100 max-h-5' : 'opacity-0 max-h-0 overflow-hidden'"
                >
                    <span>{{ displayPercents[index] }}% del cupo asignado</span>
                    <span class="text-green font-medium">Clic para registrar pedido</span>
                </div>
            </div>
        </div>

        <!-- Footer del corte de pedidos -->
        <div class="mt-7 flex items-center justify-between border-t border-cream pt-5">
            <span class="text-[13.5px] text-faint">Corte de pedidos</span>
            <div class="group relative">
                <span class="rounded-full bg-green-soft px-[11px] py-[5px] text-[13.5px] text-green font-medium flex items-center gap-1.5 cursor-default">
                    <span class="h-1.5 w-1.5 rounded-full bg-green animate-pulse"></span>
                    <span>{{ activeLocation.cutoff }}</span>
                </span>
            </div>
        </div>
    </div>
</template>
