import { defineStore } from 'pinia';
import { api } from '../api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('mf_token'),
        user: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isCateringStaff: (state) =>
            ['tenant_admin', 'kitchen_operator', 'super_admin'].includes(state.user?.role),
        initials: (state) => {
            const name = state.user?.tenant?.name ?? state.user?.name ?? '';
            return name
                .split(' ')
                .slice(0, 2)
                .map((word) => word[0] ?? '')
                .join('')
                .toUpperCase();
        },
    },

    actions: {
        async login(email, password) {
            const data = await api.post('/auth/login', {
                email,
                password,
                device_name: 'webapp',
            });
            this.token = data.token;
            this.user = data.user;
            localStorage.setItem('mf_token', data.token);
        },

        async fetchMe() {
            if (!this.token) return;
            try {
                this.user = await api.get('/auth/me');
            } catch {
                this.clear();
            }
        },

        async logout() {
            try {
                await api.post('/auth/logout');
            } finally {
                this.clear();
            }
        },

        clear() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('mf_token');
        },
    },
});
