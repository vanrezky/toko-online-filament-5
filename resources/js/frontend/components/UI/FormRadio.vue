<script setup>
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const model = defineModel();

const props = defineProps({
    class: {
        type: [String, Array, Object],
        default: undefined,
    },
});
</script>

<template>
    <input
        v-if="model !== undefined"
        v-bind="$attrs"
        v-model="model"
        type="radio"
        :class="cn('h-5 w-5 shrink-0 cursor-pointer accent-primary outline-none focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60', props.class)"
    />
    <input
        v-else
        v-bind="$attrs"
        type="radio"
        :class="cn('h-5 w-5 shrink-0 cursor-pointer accent-primary outline-none focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60', props.class)"
    />
</template>
