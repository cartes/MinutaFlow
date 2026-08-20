<script setup>
import PublicLayout from '../layouts/PublicLayout.vue';
import ProgressBar from '../components/ProgressBar.vue';
import { useLoginModal } from '../composables/useLoginModal';

const { openLoginModal } = useLoginModal();

const stats = [
    { value: '98,4%', label: 'de pedidos retirados' },
    { value: '−31%', label: 'merma de cocina' },
    { value: '6 min', label: 'para publicar el menú semanal' },
    { value: '4 roles', label: 'catering, cocina, RRHH y comensal' },
];

const roles = [
    {
        tag: 'Catering',
        title: 'Menús con cupo',
        body: 'Arma la minuta por fecha, define cupo máximo por opción y publica cuando esté lista. Nada se pide antes de tiempo.',
    },
    {
        tag: 'Cocina',
        title: 'Producción exacta',
        body: 'Al cerrar el corte, el packing list por sucursal queda listo: cantidades, alérgenos y etiquetas de despacho.',
    },
    {
        tag: 'Empresa cliente',
        title: 'Subsidio y copago',
        body: 'El porcentaje de subsidio se aplica al pedido y el copago en CLP queda registrado para la liquidación del mes.',
    },
];

const demanda = [
    { label: 'Fondo del día', value: 184, pct: 82, color: 'bg-gold' },
    { label: 'Hipocalórico', value: 92, pct: 41, color: 'bg-tan' },
    { label: 'Vegana', value: 37, pct: 17, color: 'bg-green-mid' },
];
</script>

<template>
    <PublicLayout>
        <div class="mx-auto max-w-[1440px] px-6 pb-20 lg:px-16">

            <section class="grid items-end gap-14 py-14 pb-16 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full bg-green-soft px-3 py-1.5 text-[12.5px] tracking-wide text-green">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-mid"></span>
                        Casinos y catering corporativo
                    </div>
                    <h1 class="mb-6 font-serif text-5xl leading-[1.02] tracking-tight text-balance md:text-[76px]">
                        El almuerzo de mañana,<br />cerrado hoy a las 15:00.
                    </h1>
                    <p class="mb-9 max-w-[46ch] text-[17px] leading-relaxed text-pretty text-muted">
                        MinutaFlow reúne menú, cupos y pedidos en un solo lugar. Cocina produce contra
                        demanda real, RRHH ve su subsidio al día y el comensal retira con un QR.
                    </p>
                    <div class="flex items-center gap-3">
                        <a
                            href="mailto:hola@minutaflow.cl?subject=Demo%20MinutaFlow"
                            class="rounded-xl border border-ink bg-ink px-6 py-[13px] text-[15px] text-white hover:bg-ink-hover"
                        >Agendar una demo</a>
                        <button
                            type="button"
                            class="rounded-xl border border-btn-line bg-white px-[22px] py-[13px] text-[15px] text-ink hover:bg-paper cursor-pointer transition-colors shadow-2xs"
                            @click="openLoginModal()"
                        >Ver el panel de cocina</button>
                    </div>
                </div>

                <div class="rounded-[22px] border border-card-line bg-white px-8 pt-[30px] pb-[34px] shadow-[0_24px_60px_-40px_rgba(60,50,30,0.45)]">
                    <div class="mb-[26px] flex items-baseline justify-between">
                        <span class="text-[13px] tracking-wider text-faint uppercase">Servicio de hoy</span>
                        <span class="text-[13px] text-faint">Planta Quilicura</span>
                    </div>
                    <div class="grid gap-[22px]">
                        <div v-for="row in demanda" :key="row.label">
                            <div class="mb-2 flex items-baseline justify-between">
                                <span class="text-sm text-muted">{{ row.label }}</span>
                                <span class="text-[22px] tracking-tight tabular-nums">{{ row.value }}</span>
                            </div>
                            <ProgressBar :value="row.pct" :color="row.color" height="h-[5px]" />
                        </div>
                    </div>
                    <div class="mt-7 flex items-center justify-between border-t border-cream pt-5">
                        <span class="text-[13.5px] text-faint">Corte de pedidos</span>
                        <span class="rounded-full bg-green-soft px-[11px] py-[5px] text-[13.5px] text-green">Cierra en 2 h 14 min</span>
                    </div>
                </div>
            </section>

            <section
                id="producto"
                class="mb-24 grid grid-cols-2 gap-px overflow-hidden rounded-[18px] border border-line bg-line lg:grid-cols-4"
            >
                <div v-for="stat in stats" :key="stat.label" class="bg-paper px-7 py-[26px]">
                    <div class="mb-2 font-serif text-[40px] leading-none">{{ stat.value }}</div>
                    <div class="text-[13.5px] text-muted">{{ stat.label }}</div>
                </div>
            </section>

            <section id="operacion" class="mb-24">
                <h2 class="mb-3 font-serif text-[44px] tracking-tight">Un flujo, cuatro manos</h2>
                <p class="mb-10 max-w-[56ch] text-base text-muted">
                    Cada rol ve solo lo suyo y el dato viaja solo: del menú publicado al packing list,
                    y del QR escaneado al reporte de consumo.
                </p>
                <div class="grid gap-5 md:grid-cols-3">
                    <div
                        v-for="role in roles"
                        :key="role.tag"
                        class="rounded-[18px] border border-card-line bg-white p-7"
                    >
                        <div class="mb-[18px] text-[12.5px] tracking-wider text-faint uppercase">{{ role.tag }}</div>
                        <h3 class="mb-2.5 text-xl font-semibold tracking-tight">{{ role.title }}</h3>
                        <p class="text-[14.5px] leading-relaxed text-muted">{{ role.body }}</p>
                    </div>
                </div>
            </section>

            <section
                id="precios"
                class="grid items-center gap-12 rounded-3xl bg-sand px-8 py-14 lg:grid-cols-[1fr_auto] lg:px-14"
            >
                <div>
                    <h2 class="mb-3.5 font-serif text-[46px] leading-[1.05] tracking-tight">Empieza con una sucursal.</h2>
                    <p class="max-w-[52ch] text-base leading-relaxed text-sand-text">
                        Implementación en dos semanas, sin cambiar tu carta ni tu cocina.
                        Se cobra por comensal activo al mes.
                    </p>
                </div>
                <a
                    href="mailto:hola@minutaflow.cl?subject=MinutaFlow"
                    class="rounded-xl border border-ink bg-ink px-[26px] py-3.5 text-[15px] whitespace-nowrap text-white hover:bg-ink-hover"
                >Hablar con ventas</a>
            </section>
        </div>
    </PublicLayout>
</template>
