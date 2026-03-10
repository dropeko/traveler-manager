<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        size?: number;
        class?: string;
    }>(),
    { size: 16 },
);
</script>

<template>
    <svg
        :width="props.size"
        :height="props.size"
        viewBox="0 0 24 24"
        class="animate-spin"
        :class="props.class"
        aria-hidden="true"
    >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"
        />
    </svg>
</template>