<script setup>
import { computed } from "vue";
import { Minus, Plus } from "lucide-vue-next";
import { cn } from "../../lib/utils";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: Number, default: 1 },
    min: { type: Number, default: 1 },
    max: { type: Number, default: Number.MAX_SAFE_INTEGER },
    disabled: Boolean,
    decreaseLabel: { type: String, default: "Decrease quantity" },
    increaseLabel: { type: String, default: "Increase quantity" },
    quantityLabel: { type: String, default: "Quantity" },
    class: { type: [String, Array, Object], default: "" },
});

const emit = defineEmits(["update:modelValue", "change"]);

const minimum = computed(() => (Number.isFinite(props.min) ? Math.max(0, props.min) : 1));
const maximum = computed(() =>
    Number.isFinite(props.max) ? Math.max(minimum.value, props.max) : Number.MAX_SAFE_INTEGER,
);
const value = computed(() => {
    const current = Number(props.modelValue);

    return Math.min(Math.max(Number.isFinite(current) ? current : minimum.value, minimum.value), maximum.value);
});
const canDecrease = computed(() => !props.disabled && value.value > minimum.value);
const canIncrease = computed(() => !props.disabled && value.value < maximum.value);

const changeBy = (delta) => {
    if (props.disabled) return;

    const nextValue = Math.min(Math.max(value.value + delta, minimum.value), maximum.value);
    if (nextValue === value.value) return;

    emit("update:modelValue", nextValue);
    emit("change", nextValue);
};

const stepperClass = computed(() =>
    cn("grid h-8 grid-cols-3 self-start overflow-hidden rounded-lg border border-border sm:h-9", props.class),
);
</script>

<template>
    <div v-bind="$attrs" :class="stepperClass">
        <button
            type="button"
            class="text-foreground inline-flex items-center justify-center transition-colors hover:bg-secondary/60 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="!canDecrease"
            :aria-label="decreaseLabel"
            @click="changeBy(-1)"
        >
            <Minus class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
        <output class="border-border inline-flex items-center justify-center border-x text-xs font-semibold" :aria-label="quantityLabel">
            {{ value }}
        </output>
        <button
            type="button"
            class="text-foreground inline-flex items-center justify-center transition-colors hover:bg-secondary/60 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="!canIncrease"
            :aria-label="increaseLabel"
            @click="changeBy(1)"
        >
            <Plus class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
    </div>
</template>
