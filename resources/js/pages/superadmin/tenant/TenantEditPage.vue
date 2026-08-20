<script setup>
import { ref, watch } from 'vue';
import { useSuperadminTenantStore } from '../../../stores/superadminTenant';

const store = useSuperadminTenantStore();

const saving = ref(false);
const successMessage = ref(null);
const errorMessage = ref(null);

const form = ref({
    name: '',
    rut: '',
    slug: '',
    billing_email: '',
    phone: '',
    timezone: 'America/Santiago',
    currency: 'CLP',
});

const TIMEZONES = [
    'America/Santiago',
    'America/Argentina/Buenos_Aires',
    'America/Lima',
    'America/Bogota',
    'America/Mexico_City',
    'America/Sao_Paulo',
    'UTC',
];

const CURRENCIES = ['CLP', 'ARS', 'PEN', 'COP', 'MXN', 'BRL', 'USD'];

// Rellena el formulario en cuanto el tenant esté disponible (o cambie)
watch(
    () => store.tenant,
    (tenant) => {
        if (!tenant) return;
        form.value = {
            name: tenant.name ?? '',
            rut: tenant.rut ?? '',
            slug: tenant.slug ?? '',
            billing_email: tenant.billing_email ?? '',
            phone: tenant.phone ?? '',
            timezone: tenant.timezone ?? 'America/Santiago',
            currency: tenant.currency ?? 'CLP',
        };
    },
    { immediate: true },
);

async function handleSave() {
    saving.value = true;
    successMessage.value = null;
    errorMessage.value = null;
    try {
        const res = await store.updateTenant(store.tenantId, form.value);
        successMessage.value = res.message || 'Concesionaria actualizada correctamente.';
    } catch (e) {
        errorMessage.value = e.errors
            ? Object.values(e.errors).flat().join(' ')
            : e.message || 'No se pudo guardar los cambios.';
    } finally {
        saving.value = false;
    }
}

async function toggleStatus() {
    const tenant = store.tenant;
    const newStatus = !tenant.is_active;
    const confirmMsg = newStatus
        ? `¿Deseas reactivar a la concesionaria "${tenant.name}"?`
        : `¿Deseas pausar temporalmente a la concesionaria "${tenant.name}"? Los usuarios no podrán operar.`;
    if (!confirm(confirmMsg)) return;

    saving.value = true;
    successMessage.value = null;
    errorMessage.value = null;
    try {
        await store.updateTenant(store.tenantId, { is_active: newStatus });
        successMessage.value = newStatus
            ? 'Concesionaria reactivada correctamente.'
            : 'Concesionaria pausada correctamente.';
    } catch (e) {
        errorMessage.value = e.message || 'No se pudo actualizar el estado.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="space-y-6 max-w-[860px]">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl text-ink tracking-tight">Ficha & Edición</h1>
            <p class="text-sm text-muted mt-1">
                Modifica los datos administrativos, tributarios y de configuración de la concesionaria.
            </p>
        </div>

        <!-- Feedback -->
        <div v-if="successMessage" class="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-sm text-emerald-700 font-medium">
            {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="rounded-xl bg-rust/10 border border-rust/30 p-3.5 text-sm text-rust font-medium">
            {{ errorMessage }}
        </div>

        <form class="rounded-2xl border border-card-line bg-white p-6 sm:p-8 shadow-xs space-y-5" @submit.prevent="handleSave">
            <div class="text-xs font-bold text-ink uppercase tracking-wider">Datos de la Empresa</div>

            <div class="space-y-1">
                <label class="block text-xs font-semibold text-ink">Razón Social o Nombre de Fantasía *</label>
                <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink focus:bg-white focus:border-ink outline-none"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-ink">RUT Concesionaria *</label>
                    <input
                        v-model="form.rut"
                        type="text"
                        required
                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink focus:bg-white focus:border-ink outline-none"
                    />
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-ink">Subdominio / Slug</label>
                    <input
                        v-model="form.slug"
                        type="text"
                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink font-mono focus:bg-white focus:border-ink outline-none"
                    />
                    <p class="text-[11px] text-faint">Cambiarlo afecta las URLs públicas de la concesionaria.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-ink">Correo de Facturación *</label>
                    <input
                        v-model="form.billing_email"
                        type="email"
                        required
                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink focus:bg-white focus:border-ink outline-none"
                    />
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-ink">Teléfono de Contacto</label>
                    <input
                        v-model="form.phone"
                        type="text"
                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink focus:bg-white focus:border-ink outline-none"
                    />
                </div>
            </div>

            <div class="pt-3 border-t border-line text-xs font-bold text-ink uppercase tracking-wider">Configuración Regional</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-ink">Zona Horaria</label>
                    <select
                        v-model="form.timezone"
                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink focus:bg-white focus:border-ink outline-none"
                    >
                        <option v-for="tz in TIMEZONES" :key="tz" :value="tz">{{ tz }}</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-ink">Moneda</label>
                    <select
                        v-model="form.currency"
                        class="w-full rounded-xl border border-btn-line bg-paper/60 px-3.5 py-2 text-sm text-ink focus:bg-white focus:border-ink outline-none"
                    >
                        <option v-for="currency in CURRENCIES" :key="currency" :value="currency">{{ currency }}</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button
                    type="submit"
                    :disabled="saving"
                    class="rounded-xl bg-ink px-5 py-2.5 text-sm font-medium text-white shadow-xs hover:bg-ink-hover disabled:opacity-50"
                >
                    {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </div>
        </form>

        <!-- Zona de Estado Operacional -->
        <div class="rounded-2xl border p-6 sm:p-8 shadow-xs" :class="store.tenant?.is_active ? 'border-amber-200 bg-amber-50/40' : 'border-emerald-200 bg-emerald-50/40'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-bold text-ink">
                        {{ store.tenant?.is_active ? 'Pausar Operación' : 'Reactivar Operación' }}
                    </div>
                    <p class="text-xs text-muted mt-1 max-w-md">
                        {{ store.tenant?.is_active
                            ? 'Al pausar la concesionaria, sus usuarios no podrán operar hasta que sea reactivada.'
                            : 'La concesionaria está pausada. Reactívala para restaurar el acceso de sus usuarios.' }}
                    </p>
                </div>
                <button
                    type="button"
                    :disabled="saving"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-xs disabled:opacity-50 transition-colors"
                    :class="store.tenant?.is_active ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                    @click="toggleStatus"
                >
                    {{ store.tenant?.is_active ? 'Pausar Concesionaria' : 'Reactivar Concesionaria' }}
                </button>
            </div>
        </div>
    </div>
</template>
