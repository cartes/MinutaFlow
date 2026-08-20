<script setup>
import { ref } from 'vue';
import BrandMark from '../components/BrandMark.vue';
import { useLoginModal } from '../composables/useLoginModal';

const isMobileMenuOpen = ref(false);
const { openLoginModal } = useLoginModal();

const navLinks = [
    { label: 'Producto', href: '#producto' },
    { label: 'Operación', href: '#operacion' },
    { label: 'Precios', href: '#precios' },
    { label: 'Contacto', href: '#contacto' },
];
</script>

<template>
    <div class="min-h-screen flex flex-col bg-cream text-ink antialiased selection:bg-green-soft selection:text-green-deep">
        <!-- Banner superior opcional / Anuncio -->
        <div class="bg-sand/70 border-b border-line px-4 py-2 text-center text-xs font-medium text-sand-text flex items-center justify-center gap-2">
            <span class="inline-flex items-center rounded-full bg-green/15 px-2 py-0.5 text-[11px] font-semibold text-green-deep">NUEVO</span>
            <span>Sistema de control de alérgenos y cálculo automático de copagos para RRHH.</span>
        </div>

        <!-- Header público -->
        <header class="sticky top-0 z-40 bg-cream/90 backdrop-blur-md border-b border-line/50 transition-colors">
            <div class="mx-auto max-w-[1440px] px-6 lg:px-16 flex items-center justify-between h-20">
                <router-link :to="{ name: 'landing' }" class="focus:outline-none focus:ring-2 focus:ring-green/30 rounded-lg">
                    <BrandMark size="md" />
                </router-link>

                <!-- Menú escritorio -->
                <nav class="hidden md:flex items-center gap-8 text-[14.5px] font-medium text-muted">
                    <a
                        v-for="link in navLinks"
                        :key="link.label"
                        :href="link.href"
                        class="hover:text-green transition-colors"
                    >
                        {{ link.label }}
                    </a>
                </nav>

                <!-- Acciones escritorio -->
                <div class="hidden md:flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-[10px] border border-btn-line bg-white/60 px-4 py-[9px] text-sm font-medium text-ink hover:bg-white hover:border-line hover:shadow-xs transition-all cursor-pointer"
                        @click="openLoginModal()"
                    >
                        Ingresar
                    </button>
                    <a
                        href="mailto:hola@minutaflow.cl?subject=Demo%20MinutaFlow"
                        class="rounded-[10px] border border-green bg-green px-4 py-[9px] text-sm font-medium text-white hover:bg-green-hover transition-colors shadow-xs"
                    >
                        Pedir demo
                    </a>
                </div>

                <!-- Botón menú móvil -->
                <button
                    type="button"
                    class="md:hidden flex items-center justify-center p-2 rounded-lg text-muted hover:text-ink hover:bg-paper focus:outline-none"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    aria-label="Abrir menú"
                >
                    <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer menú móvil -->
            <div
                v-show="isMobileMenuOpen"
                class="md:hidden border-b border-line bg-cream px-6 py-5 shadow-lg space-y-4"
            >
                <nav class="flex flex-col space-y-3 text-[15px] font-medium text-muted">
                    <a
                        v-for="link in navLinks"
                        :key="link.label"
                        :href="link.href"
                        class="py-1 hover:text-green"
                        @click="isMobileMenuOpen = false"
                    >
                        {{ link.label }}
                    </a>
                </nav>
                <div class="pt-3 border-t border-line/60 flex flex-col gap-2.5">
                    <button
                        type="button"
                        class="w-full text-center rounded-[10px] border border-btn-line bg-white py-2.5 text-sm font-medium text-ink hover:bg-paper cursor-pointer"
                        @click="isMobileMenuOpen = false; openLoginModal();"
                    >
                        Ingresar a la plataforma
                    </button>
                    <a
                        href="mailto:hola@minutaflow.cl?subject=Demo%20MinutaFlow"
                        class="w-full text-center rounded-[10px] bg-green py-2.5 text-sm font-medium text-white hover:bg-green-hover"
                    >
                        Solicitar demostración
                    </a>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer institucional -->
        <footer id="contacto" class="border-t border-line bg-sand/30 pt-16 pb-12 mt-auto text-ink">
            <div class="mx-auto max-w-[1440px] px-6 lg:px-16">
                <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-5 pb-12 border-b border-line">
                    <!-- Columna Marca -->
                    <div class="lg:col-span-2 space-y-4">
                        <BrandMark size="md" />
                        <p class="max-w-sm text-sm leading-relaxed text-muted">
                            Plataforma inteligente de gestión de minutas, comedores corporativos y concesiones de alimentación. Menús contra demanda real, control de subsidios y entrega con QR.
                        </p>
                        <div class="pt-2 text-xs text-faint space-y-1">
                            <p>Santiago, Chile · Región Metropolitana</p>
                            <p class="font-mono text-muted">hola@minutaflow.cl</p>
                        </div>
                    </div>

                    <!-- Columna Soluciones -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-faint mb-4">Soluciones</h4>
                        <ul class="space-y-2.5 text-sm text-muted">
                            <li><a href="#operacion" class="hover:text-green transition-colors">Para Concesionarias</a></li>
                            <li><a href="#operacion" class="hover:text-green transition-colors">Cocina y Despacho</a></li>
                            <li><a href="#operacion" class="hover:text-green transition-colors">RRHH y Empresas</a></li>
                            <li><a href="#operacion" class="hover:text-green transition-colors">App del Comensal</a></li>
                        </ul>
                    </div>

                    <!-- Columna Plataforma -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-faint mb-4">Plataforma</h4>
                        <ul class="space-y-2.5 text-sm text-muted">
                            <li><a href="#producto" class="hover:text-green transition-colors">Planificador de Menús</a></li>
                            <li><a href="#producto" class="hover:text-green transition-colors">Gestión de Alérgenos</a></li>
                            <li><a href="#precios" class="hover:text-green transition-colors">Planes y Precios</a></li>
                            <li><button type="button" @click="openLoginModal()" class="hover:text-green transition-colors cursor-pointer text-left">Portal de Acceso</button></li>
                        </ul>
                    </div>

                    <!-- Columna Legal y Soporte -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-faint mb-4">Recursos & Legal</h4>
                        <ul class="space-y-2.5 text-sm text-muted">
                            <li><a href="mailto:soporte@minutaflow.cl" class="hover:text-green transition-colors">Soporte Técnico</a></li>
                            <li><a href="#" class="hover:text-green transition-colors">Términos del Servicio</a></li>
                            <li><a href="#" class="hover:text-green transition-colors">Política de Privacidad</a></li>
                            <li><a href="#" class="hover:text-green transition-colors">Seguridad de Datos</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Barra inferior copyright -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-faint">
                    <p>© {{ new Date().getFullYear() }} MinutaFlow. Todos los derechos reservados.</p>
                    <div class="flex items-center gap-6">
                        <span class="inline-flex items-center gap-1.5 text-muted">
                            <span class="h-2 w-2 rounded-full bg-green-mid inline-block animate-pulse"></span>
                            Sistemas Operativos en Línea
                        </span>
                        <span>CLP ($) Chile</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
