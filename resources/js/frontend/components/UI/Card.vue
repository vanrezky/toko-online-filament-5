<script setup>
import { computed, useAttrs } from "vue";
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    as: { type: [String, Object], default: "div" },
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "elevated", "flat", "dashed"].includes(value),
    },
    class: { type: [String, Array, Object], default: "" },
});

const attrs = useAttrs();

const variantClasses = {
    default: "rounded-lg border border-border bg-card text-card-foreground shadow-sm",
    elevated: "rounded-xl border border-border bg-card text-card-foreground shadow-lg",
    flat: "rounded-lg bg-card text-card-foreground",
    dashed: "rounded-lg border border-dashed border-border bg-card text-card-foreground",
};

const cardClass = computed(() => cn(variantClasses[props.variant] || variantClasses.default, props.class));
</script>

<template>
    <component :is="props.as" v-bind="attrs" :class="cardClass">
        <slot />
    </component>
</template>
