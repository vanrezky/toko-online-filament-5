---
name: fe-ui
description: Create reusable UI components for the e-commerce frontend. Follow existing patterns for cards, buttons, badges, forms, and layout primitives.
license: MIT
compatibility: opencode
metadata:
  category: frontend
  type: ui-components
---

## What I do

I help create reusable UI components following this project's established patterns.

## Existing UI Components (DO NOT Duplicate)

**Location:** `resources/js/frontend/components/UI/`

| Component | Props | Purpose |
|-----------|-------|---------|
| `ProductCard.vue` | `product` (Object), `size` (String: small/normal/large) | Product card with image, badges, wishlist, add-to-cart |
| `VoucherCard.vue` | `voucher` (Object), `variant` (String: default/compact/horizontal), `applied` (Boolean) | Voucher display with copy/apply |
| `FlashSaleCard.vue` | `flashsale` (Object) | Flash sale countdown card |
| `CategoryMenu.vue` | `categories` (Array) | Category navigation menu |
| `HeroSection.vue` | `title`, `subtitle`, `imageUrl`, `overlayColor`, `buttonText`, `buttonLink`, `badge` | Homepage hero banner |
| `FeaturedProducts.vue` | `products` (Array), `title`, `subtitle` | Featured product section |
| `FlashSaleSection.vue` | `flashsales`, `title`, `subtitle` | Flash sale section |
| `VoucherSection.vue` | `title`, `subtitle`, `limit` (Number) | Voucher list section |
| `AllProductsGrid.vue` | `products` (Array) | Product grid section |
| `HeroCarousel.vue` | `slides` (Array) | Image carousel |
| `PromotionBanner.vue` | `promotions` (Array) | Promo banner strip |
| `CartSlidePanel.vue` | — | Slide-out cart panel |

## Creating a New UI Component

**Location:** `resources/js/frontend/components/UI/YourComponent.vue`

```vue
<script setup>
import { computed } from "vue";
import { cn } from "../../lib/utils";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "compact"].includes(value),
    },
});

const classes = computed(() => {
    return cn(
        "base-classes",
        props.variant === "compact" && "compact-classes"
    );
});
</script>

<template>
    <div :class="classes">
        <h3 class="text-lg font-semibold text-foreground">{{ title }}</h3>
        <slot />
    </div>
</template>
```

## Card Component Pattern

```vue
<template>
    <div class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-primary/10">
        <!-- Image area -->
        <div class="relative aspect-square overflow-hidden bg-slate-100">
            <img v-if="image" :src="image" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
            <!-- Gradient overlay on hover -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            <!-- Badge -->
            <div v-if="badge" class="absolute left-3 top-3 px-2.5 py-1 text-xs font-bold" :class="badge.class">
                {{ badge.text }}
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex flex-grow flex-col p-4">
            <h3 class="mb-2 line-clamp-2 text-sm font-bold text-foreground">{{ title }}</h3>
            <div class="mt-auto flex items-end justify-between">
                <span class="text-sm font-bold text-primary">{{ formatCurrency(price) }}</span>
                <slot name="action" />
            </div>
        </div>
    </div>
</template>
```

## Button Component Variants

```vue
<script setup>
import { cn } from "../../lib/utils";

const props = defineProps({
    variant: {
        type: String,
        default: "primary",
        validator: (v) => ["primary", "secondary", "outline", "ghost", "destructive"].includes(v),
    },
    size: {
        type: String,
        default: "md",
        validator: (v) => ["sm", "md", "lg"].includes(v),
    },
});

const buttonClasses = computed(() => cn(
    "inline-flex items-center justify-center gap-2 font-semibold transition-all duration-300",
    // Sizes
    props.size === "sm" && "rounded-lg px-4 py-2 text-xs",
    props.size === "md" && "rounded-xl px-6 py-3 text-sm",
    props.size === "lg" && "rounded-2xl px-8 py-4 text-base",
    // Variants
    props.variant === "primary" && "bg-gradient-to-r from-primary to-primary/90 text-primary-foreground shadow-lg shadow-primary/30 hover:shadow-xl",
    props.variant === "secondary" && "bg-secondary text-secondary-foreground hover:bg-secondary/80",
    props.variant === "outline" && "border-2 border-primary text-primary hover:bg-primary hover:text-primary-foreground",
    props.variant === "ghost" && "text-foreground hover:bg-muted",
    props.variant === "destructive" && "bg-destructive text-destructive-foreground hover:bg-destructive/90",
));
</script>

<template>
    <button :class="buttonClasses">
        <slot />
    </button>
</template>
```

## Form Input Patterns

```vue
<!-- Text input with label and error -->
<div class="space-y-1.5">
    <label class="text-sm font-medium text-foreground">{{ label }}</label>
    <input
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-foreground shadow-sm transition-all duration-200 placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
        :class="error && 'border-destructive focus:border-destructive focus:ring-destructive/20'"
    />
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
</div>

<!-- Select dropdown -->
<div class="space-y-1.5">
    <label class="text-sm font-medium text-foreground">{{ label }}</label>
    <select
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        class="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
    >
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</div>

<!-- Toggle switch -->
<button
    type="button"
    @click="$emit('update:modelValue', !modelValue)"
    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
    :class="modelValue ? 'bg-primary' : 'bg-muted'"
>
    <span
        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
        :class="modelValue ? 'translate-x-6' : 'translate-x-1'"
    />
</button>
```

## Badge Patterns

```vue
<!-- Status badge -->
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
    :class="{
        'bg-green-100 text-green-800': status === 'active',
        'bg-yellow-100 text-yellow-800': status === 'pending',
        'bg-red-100 text-red-800': status === 'inactive',
    }"
>
    {{ statusLabel }}
</span>

<!-- Pill badge -->
<span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
    <Dot class="h-2 w-2 fill-current" />
    {{ label }}
</span>
```

## Loading State Pattern

```vue
<!-- Skeleton loader -->
<div class="animate-pulse space-y-4">
    <div class="h-48 rounded-2xl bg-muted"></div>
    <div class="h-4 w-3/4 rounded bg-muted"></div>
    <div class="h-4 w-1/2 rounded bg-muted"></div>
</div>

<!-- Button loading -->
<button :disabled="loading" class="...">
    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
    <span v-else>Submit</span>
</button>
```

## Modal/Dialog Pattern (with vue3-confirm-dialog)

```vue
<script setup>
import { createConfirmDialog } from "vue3-confirm-dialog";

const { reveal, onConfirm, onCancel } = createConfirmDialog(
    ConfirmModal,
    { title: "Delete item?", description: "This action cannot be undone." }
);

onConfirm(() => {
    // Perform delete
});

const showDelete = () => reveal();
</script>
```

## When to use me

- Creating new reusable components
- Extracting repeated UI patterns
- Building component libraries
- Adding interactive elements

## Important Notes

- **ALWAYS use `cn()`** from `lib/utils.js` for class composition
- **ALWAYS use `formatCurrency()`** for prices
- Use `lucide-vue-next` for icons (tree-shakeable)
- Components should be self-contained and reusable
- Use `defineProps` and `defineEmits` for API
- Use scoped slots for flexible content areas
