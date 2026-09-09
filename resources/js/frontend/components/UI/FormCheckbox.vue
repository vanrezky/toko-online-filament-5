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
        :class="
            cn(
                'form-checkbox border-border accent-primary focus-visible:ring-primary/50 h-5 w-5 shrink-0 cursor-pointer rounded bg-white outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                props.class,
            )
        "
    />
</template>
