import { defineStore } from 'pinia';
import { api } from '../api';

/**
 * Store del tenant actualmente inspeccionado por el Super Admin.
 * Lo comparten el sidebar (sub-menú contextual) y las páginas de detalle.
 */
export const useSuperadminTenantStore = defineStore('superadminTenant', {
    state: () => ({
        tenant: null,
        loading: false,
        error: null,
    }),

    getters: {
        tenantId: (state) => state.tenant?.id ?? null,
        tenantName: (state) => state.tenant?.name ?? '',
    },

    actions: {
        async fetchTenant(id, { force = false } = {}) {
            if (!force && this.tenant?.id === id) return this.tenant;
            this.loading = true;
            this.error = null;
            try {
                const res = await api.get(`/superadmin/tenants/${id}`);
                this.tenant = res.data;
                return this.tenant;
            } catch (e) {
                this.error = e.message || 'No se pudo cargar la concesionaria.';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async updateTenant(id, payload) {
            const res = await api.put(`/superadmin/tenants/${id}`, payload);
            // El update no retorna companies.branches; conservamos lo ya cargado
            this.tenant = { ...this.tenant, ...res.data };
            return res;
        },

        clear() {
            this.tenant = null;
            this.error = null;
        },
    },
});
