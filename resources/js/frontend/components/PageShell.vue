<script setup>
import { cn } from "../lib/utils";

const props = defineProps({
    class: { type: [String, Array, Object], default: "" },
    title: { type: String, default: "" },
    description: { type: String, default: "" },
    container: { type: Boolean, default: false },
    standalone: { type: Boolean, default: false },
});
</script>

<template>
    <div :class="cn('min-h-screen py-8 font-sans md:py-12', props.class)">
        <div v-if="!props.standalone" :class="props.container ? 'container mx-auto max-w-7xl px-4' : ''">
            <header v-if="props.title || props.description || $slots.actions" class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 v-if="props.title" class="text-foreground text-2xl font-bold md:text-3xl">{{ props.title }}</h1>
                    <p v-if="props.description" class="text-muted-foreground mt-1 text-sm">{{ props.description }}</p>
                </div>
                <div v-if="$slots.actions" class="shrink-0">
                    <slot name="actions" />
                </div>
            </header>
            <slot />
        </div>
        <slot v-else />
    </div>
</template>
