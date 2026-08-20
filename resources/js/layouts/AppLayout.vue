<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import TenantAdminLayout from './TenantAdminLayout.vue';
import SuperAdminLayout from './SuperAdminLayout.vue';

const auth = useAuthStore();

const layoutComponent = computed(() => {
    return auth.isSuperAdmin ? SuperAdminLayout : TenantAdminLayout;
});
</script>

<template>
    <component :is="layoutComponent">
        <router-view />
    </component>
</template>

