<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 }, // 0..100
    color: { type: String, default: 'bg-green' },
    track: { type: String, default: 'bg-cream' },
    height: { type: String, default: 'h-1' },
});

const width = computed(() => `${Math.max(0, Math.min(100, props.value))}%`);
</script>

<template>
    <div class="rounded-full" :class="[track, height]">
        <div class="h-full rounded-full" :class="color" :style="{ width }"></div>
    </div>
</template>
