<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 }, // 0..100
    color: { type: String, default: 'bg-green' },
    track: { type: String, default: 'bg-cream' },
    height: { type: String, default: 'h-1' },
    animateOnMount: { type: Boolean, default: true },
    delay: { type: Number, default: 0 }, // ms delay before growth starts
    duration: { type: Number, default: 1000 }, // ms animation duration
});

const currentWidth = ref(props.animateOnMount ? 0 : props.value);

const targetWidth = computed(() => Math.max(0, Math.min(100, props.value)));

function triggerGrowth() {
    if (props.delay > 0) {
        setTimeout(() => {
            currentWidth.value = targetWidth.value;
        }, props.delay);
    } else {
        requestAnimationFrame(() => {
            currentWidth.value = targetWidth.value;
        });
    }
}

onMounted(() => {
    if (props.animateOnMount) {
        // Small tick to ensure browser has rendered initial 0% before transitioning
        setTimeout(() => {
            triggerGrowth();
        }, 50);
    }
});

watch(() => props.value, (newVal) => {
    currentWidth.value = Math.max(0, Math.min(100, newVal));
});
</script>

<template>
    <div
        class="w-full rounded-full overflow-hidden relative transition-all"
        :class="[track, height]"
    >
        <div
            class="h-full rounded-full transition-[width] ease-[cubic-bezier(0.25,1,0.5,1)] will-change-[width]"
            :class="color"
            :style="{
                width: `${currentWidth}%`,
                transitionDuration: `${duration}ms`,
                transitionDelay: `${delay}ms`
            }"
        ></div>
    </div>
</template>
