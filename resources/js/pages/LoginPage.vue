<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BrandMark from '../components/BrandMark.vue';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref(null);

async function submit() {
    loading.value = true;
    error.value = null;
    try {
        await auth.login(email.value, password.value);
        router.push(route.query.redirect ?? { name: 'panel' });
    } catch (e) {
        error.value = e.errors?.email?.[0] ?? e.message ?? 'No se pudo iniciar sesión.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-cream px-6">
        <div class="w-full max-w-[400px]">
            <div class="mb-8 flex justify-center">
                <router-link :to="{ name: 'landing' }"><BrandMark /></router-link>
            </div>

            <div class="rounded-[22px] border border-card-line bg-white px-8 pt-8 pb-9 shadow-[0_24px_60px_-40px_rgba(60,50,30,0.45)]">
                <h1 class="mb-1 font-serif text-[32px] tracking-tight">Ingresar</h1>
                <p class="mb-7 text-sm text-muted">Accede al panel de tu catering.</p>

                <form class="grid gap-4" @submit.prevent="submit">
                    <label class="grid gap-1.5">
                        <span class="text-[13px] text-muted">Correo</span>
                        <input
                            v-model="email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="tu@empresa.cl"
                            class="rounded-[10px] border border-btn-line bg-white px-3.5 py-2.5 text-[15px] outline-none focus:border-green"
                        />
                    </label>
                    <label class="grid gap-1.5">
                        <span class="text-[13px] text-muted">Contraseña</span>
                        <input
                            v-model="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="rounded-[10px] border border-btn-line bg-white px-3.5 py-2.5 text-[15px] outline-none focus:border-green"
                        />
                    </label>

                    <p v-if="error" class="rounded-[10px] bg-rust/10 px-3.5 py-2.5 text-[13.5px] text-rust">
                        {{ error }}
                    </p>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="mt-1 rounded-xl border border-green bg-green py-3 text-[15px] text-white hover:bg-green-hover disabled:opacity-60"
                    >{{ loading ? 'Ingresando…' : 'Ingresar' }}</button>
                </form>
            </div>

            <p class="mt-6 text-center text-[13px] text-faint">
                ¿Sin cuenta? Escríbenos a hola@minutaflow.cl
            </p>
        </div>
    </div>
</template>
