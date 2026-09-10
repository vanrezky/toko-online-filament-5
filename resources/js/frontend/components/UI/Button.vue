<script setup>
import { computed, useAttrs, useSlots } from "vue";
import { LoaderCircle } from "lucide-vue-next";
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    as: { type: [String, Object], default: "button" },
    type: { type: String, default: "button" },
    variant: {
        type: String,
        default: "unstyled",
        validator: (value) => ["unstyled", "primary", "secondary", "outline", "ghost", "destructive"].includes(value),
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg", "icon"].includes(value),
    },
    icon: { type: [String, Object, Function], default: null },
    iconProps: { type: Object, default: () => ({}) },
    iconPosition: {
        type: String,
        default: "left",
        validator: (value) => ["left", "right"].includes(value),
    },
    loading: Boolean,
    block: Boolean,
    class: { type: [String, Array, Object], default: "" },
});

const attrs = useAttrs();
const slots = useSlots();

const variantClasses = {
    unstyled: "border-transparent bg-transparent text-current",
    primary: "border-transparent bg-primary text-primary-foreground hover:bg-primary/90",
    secondary: "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
    outline: "border-border bg-transparent text-foreground hover:border-primary hover:text-primary hover:bg-transparent",
    ghost: "border-transparent bg-transparent text-foreground hover:bg-muted",
    destructive: "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/90",
};

const sizeClasses = {
    sm: "min-h-8 px-3 py-1.5 text-xs",
    md: "min-h-10 px-4 py-2.5 text-sm",
    lg: "min-h-11 px-5 py-3 text-sm",
    icon: "h-10 w-10 p-2",
};

const isNativeButton = computed(() => props.as === "button");
const isDisabled = computed(() => Boolean(props.loading || attrs.disabled));
const isDisabledLink = computed(() => !isNativeButton.value && isDisabled.value);

const buttonClass = computed(() =>
    cn(
        "inline-flex items-center justify-center gap-2 rounded-xl border text-center font-semibold transition-all active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:pointer-events-none disabled:opacity-50",
        variantClasses[props.variant] || variantClasses.unstyled,
        sizeClasses[props.size] || sizeClasses.md,
        props.block && "w-full",
        isDisabledLink.value && "pointer-events-none opacity-50",
        props.class,
    ),
);

const component = computed(() => props.as);
const hasIconSlot = computed(() => Boolean(slots.icon));

const preventDisabledNavigation = (event) => {
    if (isDisabledLink.value) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
};
</script>

<template>
    <component
        :is="component"
        v-bind="attrs"
        :type="isNativeButton ? props.type : undefined"
        :disabled="isNativeButton ? isDisabled : undefined"
        :class="buttonClass"
        :aria-busy="props.loading || undefined"
        :aria-disabled="isDisabledLink || undefined"
        :tabindex="isDisabledLink ? -1 : attrs.tabindex"
        @click.capture="preventDisabledNavigation"
    >
        <LoaderCircle v-if="props.loading" class="h-4 w-4 animate-spin" aria-hidden="true" />
        <template v-else-if="props.iconPosition === 'left'">
            <component v-if="props.icon" :is="props.icon" v-bind="props.iconProps" class="h-4 w-4 shrink-0" aria-hidden="true" />
            <slot v-if="hasIconSlot" name="icon" />
        </template>

        <slot />

        <template v-if="!props.loading && props.iconPosition === 'right'">
            <component v-if="props.icon" :is="props.icon" v-bind="props.iconProps" class="h-4 w-4 shrink-0" aria-hidden="true" />
            <slot v-if="hasIconSlot" name="icon" />
        </template>
    </component>
</template>
