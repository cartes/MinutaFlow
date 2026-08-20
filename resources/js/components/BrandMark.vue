<script setup>
defineProps({
    size: { type: String, default: 'md' }, // md (landing) | sm (sidebar)
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div
            class="flex items-center justify-center rounded-lg bg-green"
            :class="size === 'md' ? 'h-[26px] w-[26px]' : 'h-6 w-6'"
        >
            <div
                class="rounded-[3px] bg-sand"
                :class="size === 'md' ? 'h-[9px] w-[9px]' : 'h-2 w-2'"
            ></div>
        </div>
        <span
            class="font-semibold tracking-tight"
            :class="size === 'md' ? 'text-[17px]' : 'text-[15.5px]'"
        >MinutaFlow</span>
    </div>
</template>
