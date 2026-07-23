<script setup>
import { PhCaretDown } from "@phosphor-icons/vue";

import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const model = defineModel({ default: "" });

const props = defineProps({
    invalid: Boolean,
    class: {
        type: [String, Array, Object],
        default: undefined,
    },
});
</script>

<template>
    <div class="relative">
        <select
            v-bind="$attrs"
            v-model="model"
            :aria-invalid="invalid || $attrs['aria-invalid'] || undefined"
            :class="cn(
                'min-h-11 min-w-0 w-full cursor-pointer appearance-none rounded-xl border border-border bg-secondary px-4 py-3 pr-11 text-sm text-foreground transition-colors outline-none hover:border-primary/50 focus-visible:border-primary focus-visible:bg-background focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20',
                invalid &&
                    'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20',
                props.class,
            )"
        >
            <slot />
        </select>
        <PhCaretDown
            aria-hidden="true"
            :size="16"
            class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground"
        />
    </div>
</template>
