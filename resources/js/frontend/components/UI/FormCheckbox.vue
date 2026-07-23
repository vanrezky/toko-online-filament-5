<script setup>
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const model = defineModel({ default: false });

const props = defineProps({
    class: {
        type: [String, Array, Object],
        default: undefined,
    },
});
</script>

<template>
    <input
        v-bind="$attrs"
        v-model="model"
        type="checkbox"
        :class="cn('h-4 w-4 rounded border-border bg-secondary text-primary outline-none focus-visible:ring-1 focus-visible:ring-primary/50 disabled:cursor-not-allowed disabled:opacity-60', props.class)"
    />
</template>
