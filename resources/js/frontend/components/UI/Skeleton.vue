<script setup>
defineProps({
    as: { type: String, default: "div" },
    customClass: { type: String, default: "" },
});
</script>

<template>
    <component :is="as" :class="['skeleton-shimmer bg-secondary/70', customClass]" aria-hidden="true">
        <slot />
    </component>
</template>

<style scoped>
.skeleton-shimmer {
    background-image: linear-gradient(90deg, hsl(var(--secondary)) 0%, hsl(var(--background) / 0.7) 50%, hsl(var(--secondary)) 100%);
    background-size: 200% 100%;
    animation: skeleton-shimmer 1.8s ease-in-out infinite;
}

@keyframes skeleton-shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .skeleton-shimmer {
        animation: none;
    }
}
</style>
