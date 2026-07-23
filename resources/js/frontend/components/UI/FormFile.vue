<script setup>
import { ref } from "vue";
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    invalid: Boolean,
    class: {
        type: [String, Array, Object],
        default: undefined,
    },
});

const input = ref(null);

defineExpose({
    click: () => input.value?.click(),
    element: input,
});
</script>

<template>
    <input
        ref="input"
        v-bind="$attrs"
        type="file"
        :aria-invalid="invalid || $attrs['aria-invalid'] || undefined"
        :class="cn(
            'min-h-11 min-w-0 w-full cursor-pointer rounded-xl border border-border bg-secondary px-3 py-2 text-sm text-foreground transition-colors outline-none hover:border-primary/50 hover:bg-background focus-visible:border-primary focus-visible:bg-background focus-visible:ring-2 focus-visible:ring-primary/25 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-60 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/20 file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-foreground file:transition-colors hover:file:bg-primary/90',
            invalid && 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20',
            props.class,
        )"
    />
</template>
