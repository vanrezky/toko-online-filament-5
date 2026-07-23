<script setup>
import { computed, ref, useSlots } from "vue";
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const model = defineModel({ default: "" });
const slots = useSlots();
const input = ref(null);

const props = defineProps({
    invalid: Boolean,
    borderless: Boolean,
    wrapperClass: {
        type: [String, Array, Object],
        default: undefined,
    },
    class: {
        type: [String, Array, Object],
        default: undefined,
    },
});

const hasPrefix = computed(() => Boolean(slots.prefix));
const hasSuffix = computed(() => Boolean(slots.suffix));

defineExpose({
    focus: () => input.value?.focus(),
    element: input,
});
</script>

<template>
    <div :class="cn('relative', props.wrapperClass)">
        <slot name="prefix" />
        <input
            ref="input"
            v-bind="$attrs"
            v-model="model"
            :aria-invalid="invalid || $attrs['aria-invalid'] || undefined"
            :class="cn(
                'min-h-11 min-w-0 w-full rounded-xl px-4 py-3 text-sm text-foreground transition-colors outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-60',
                props.borderless
                ? 'border-0 focus-visible:border-0 focus-visible:ring-0'
                : 'border border-border bg-secondary hover:border-primary/50 focus-visible:border-primary focus-visible:bg-background focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20',
                hasPrefix && 'pl-11',
                hasSuffix && 'pr-11',
                invalid && 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20',
                props.class,
            )"
        />
        <slot name="suffix" />
    </div>
</template>
