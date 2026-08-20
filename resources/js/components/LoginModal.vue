<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useLoginModal } from '../composables/useLoginModal';
import BrandMark from './BrandMark.vue';

const { isLoginModalOpen, redirectTarget, closeLoginModal } = useLoginModal();
const auth = useAuthStore();
const router = useRouter();

const emailInput = ref(null);
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const error = ref(null);
const isSuccess = ref(false);
const hasShakeError = ref(false);

// Autofocus al abrir y limpiar estados
watch(isLoginModalOpen, async (isOpen) => {
    if (isOpen) {
        error.value = null;
        isSuccess.value = false;
        hasShakeError.value = false;
        await nextTick();
        emailInput.value?.focus();
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

function handleKeydown(e) {
    if (e.key === 'Escape' && isLoginModalOpen.value && !loading.value) {
        closeLoginModal();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});

async function submit() {
    if (loading.value) return;
    loading.value = true;
    error.value = null;
    hasShakeError.value = false;

    try {
        await auth.login(email.value, password.value);
        isSuccess.value = true;
        
        // Breve pausa para mostrar animación de éxito antes de redirigir
        setTimeout(() => {
            closeLoginModal();
            const target = redirectTarget.value ?? { name: 'panel' };
            router.push(target);
            // Reset fields
            email.value = '';
            password.value = '';
            isSuccess.value = false;
        }, 650);
    } catch (e) {
        error.value = e.errors?.email?.[0] ?? e.message ?? 'Credenciales incorrectas o problema de conexión.';
        hasShakeError.value = true;
        setTimeout(() => {
            hasShakeError.value = false;
        }, 600);
    } finally {
        loading.value = false;
    }
}

function handleBackdropClick(e) {
    if (e.target === e.currentTarget && !loading.value) {
        closeLoginModal();
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-bounce" :duration="{ enter: 450, leave: 250 }">
            <div
                v-if="isLoginModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 select-none overflow-y-auto"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-login-title"
                @mousedown="handleBackdropClick"
            >
                <!-- Backdrop con efecto Glassmorphism suave -->
                <div class="modal-backdrop fixed inset-0 bg-ink/45 backdrop-blur-md transition-opacity"></div>

                <!-- Contenedor del Modal con animación Bounce desde el centro -->
                <div
                    class="modal-card relative w-full max-w-[420px] rounded-[26px] border border-white/60 bg-white/95 p-7 sm:p-8 shadow-[0_25px_70px_-15px_rgba(27,26,23,0.35),0_0_0_1px_rgba(0,0,0,0.04)] backdrop-blur-xl z-10"
                    :class="{ 'animate-shake': hasShakeError }"
                    @mousedown.stop
                >
                    <!-- Botón cerrar (X) flotante elegante -->
                    <button
                        type="button"
                        class="absolute top-5 right-5 flex h-8 w-8 items-center justify-center rounded-full bg-paper text-muted hover:bg-line hover:text-ink transition-colors focus:outline-none focus:ring-2 focus:ring-green/30"
                        @click="closeLoginModal"
                        aria-label="Cerrar modal"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Cabecera de Marca -->
                    <div class="mb-6 flex flex-col items-center text-center">
                        <div class="mb-3 inline-flex items-center justify-center p-2 rounded-2xl bg-cream/70 border border-line/60 shadow-xs">
                            <BrandMark size="md" />
                        </div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-green-soft px-3 py-1 text-[11.5px] font-medium tracking-wide text-green">
                            <span class="h-1.5 w-1.5 rounded-full bg-green-mid animate-pulse"></span>
                            Acceso seguro a la plataforma
                        </div>
                        <h2 id="modal-login-title" class="mt-3 font-serif text-[28px] leading-tight text-ink tracking-tight">
                            Ingresar a MinutaFlow
                        </h2>
                        <p class="mt-1 text-[13.5px] text-muted">
                            Gestiona pedidos, menús y despachos en tiempo real.
                        </p>
                    </div>

                    <!-- Formulario -->
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <label for="login-email" class="block text-[13px] font-medium text-ink">
                                Correo institucional
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-faint">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                    </svg>
                                </span>
                                <input
                                    id="login-email"
                                    ref="emailInput"
                                    v-model="email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    placeholder="nombre@catering.cl"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 pl-10 pr-3.5 py-2.5 text-[14.5px] text-ink placeholder:text-faint focus:bg-white focus:border-green focus:ring-2 focus:ring-green/20 outline-none transition-all"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="login-password" class="block text-[13px] font-medium text-ink">
                                    Contraseña
                                </label>
                                <a
                                    href="mailto:soporte@minutaflow.cl?subject=Recuperar%20acceso"
                                    class="text-[12px] text-muted hover:text-green hover:underline"
                                    tabindex="-1"
                                >
                                    ¿Olvidaste tu clave?
                                </a>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-faint">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <input
                                    id="login-password"
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-btn-line bg-paper/60 pl-10 pr-10 py-2.5 text-[14.5px] text-ink placeholder:text-faint focus:bg-white focus:border-green focus:ring-2 focus:ring-green/20 outline-none transition-all"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-faint hover:text-ink transition-colors focus:outline-none"
                                    @click="showPassword = !showPassword"
                                    tabindex="-1"
                                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
                                >
                                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Feedback de Error -->
                        <div
                            v-if="error"
                            class="flex items-center gap-2 rounded-xl bg-rust/12 px-3.5 py-2.5 text-[13px] font-medium text-rust border border-rust/20"
                            role="alert"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ error }}</span>
                        </div>

                        <!-- Botón Submit con estados dinámicos -->
                        <button
                            type="submit"
                            :disabled="loading || isSuccess"
                            class="group relative flex w-full items-center justify-center gap-2 rounded-xl border border-green bg-green py-3 text-[14.5px] font-medium text-white shadow-xs hover:bg-green-hover hover:shadow-md active:scale-[0.98] disabled:opacity-75 disabled:pointer-events-none transition-all"
                        >
                            <!-- Estado de éxito -->
                            <template v-if="isSuccess">
                                <svg class="h-5 w-5 animate-scale-in text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>¡Acceso concedido! Entrando…</span>
                            </template>

                            <!-- Estado cargando -->
                            <template v-else-if="loading">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span>Autenticando…</span>
                            </template>

                            <!-- Estado normal -->
                            <template v-else>
                                <span>Iniciar sesión</span>
                                <svg class="h-4 w-4 transform transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </template>
                        </button>
                    </form>

                    <!-- Pie del modal -->
                    <div class="mt-6 border-t border-line/70 pt-4 text-center text-xs text-muted">
                        ¿Necesitas acceso para tu empresa?
                        <a
                            href="mailto:hola@minutaflow.cl?subject=Solicitud%20de%20Acceso%20MinutaFlow"
                            class="font-medium text-green hover:underline ml-1"
                        >
                            Solicitar cuenta demo
                        </a>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Transición con animación de Elastic Bounce desde el centro */
.modal-bounce-enter-active {
    transition: opacity 0.35s ease-out;
}

.modal-bounce-leave-active {
    transition: opacity 0.25s ease-in;
}

.modal-bounce-enter-active .modal-backdrop {
    animation: fadeInBackdrop 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-bounce-leave-active .modal-backdrop {
    animation: fadeOutBackdrop 0.25s cubic-bezier(0.7, 0, 0.84, 0) forwards;
}

.modal-bounce-enter-active .modal-card {
    animation: springCenterBounce 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    transform-origin: center center;
}

.modal-bounce-leave-active .modal-card {
    animation: shrinkCenter 0.22s cubic-bezier(0.4, 0, 1, 1) forwards;
    transform-origin: center center;
}

@keyframes fadeInBackdrop {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOutBackdrop {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

/* Animación que nace del centro exacto con un sutil y sofisticado bounce elástico */
@keyframes springCenterBounce {
    0% {
        opacity: 0;
        transform: scale(0.65);
    }
    60% {
        opacity: 1;
        transform: scale(1.035);
    }
    82% {
        transform: scale(0.985);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes shrinkCenter {
    0% {
        opacity: 1;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(0.75);
    }
}

/* Shake de error sutil cuando falla la autenticación */
.animate-shake {
    animation: shakeError 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes shakeError {
    10%, 90% {
        transform: translate3d(-2px, 0, 0);
    }
    20%, 80% {
        transform: translate3d(3px, 0, 0);
    }
    30%, 50%, 70% {
        transform: translate3d(-4px, 0, 0);
    }
    40%, 60% {
        transform: translate3d(4px, 0, 0);
    }
}

@keyframes scaleIn {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-scale-in {
    animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
</style>
