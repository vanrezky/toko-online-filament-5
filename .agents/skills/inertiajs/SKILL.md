---
name: inertiajs
description: Use Inertia.js with Vue 3 in this Laravel project. Follow these patterns for pages, routing, forms, and data passing between backend and frontend.
license: MIT
compatibility: opencode
metadata:
  category: frontend-framework
  framework: inertia-vue3
---

## What I do

I help write Inertia.js code for this Laravel + Vue 3 project following the established patterns.

## Project Setup

**Entry point:** `resources/js/frontend/main.js`
- Uses `createInertiaApp` from `@inertiajs/vue3`
- Uses `resolvePageComponent` from `laravel-vite-plugin/inertia-helpers`
- Uses `ZiggyVue` for route generation
- Registered plugins: `ConfirmDialog` (vue3-confirm-dialog)

**Page resolution:**
```js
resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob("./pages/**/*.vue"))
```

Pages are auto-resolved from `resources/js/frontend/pages/`. Controller returns `Inertia::render('PageName', [...])` which maps to `pages/PageName.vue`.

## Page Structure

Every page uses `<script setup>` and wraps content in `<TemplateWrapper>`:

```vue
<script setup>
import { computed, ref, watch } from "vue";
import { usePage, Link, router, useForm } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
// ... other imports

const props = defineProps({
    items: Object,      // Paginated data
    categories: Array,  // Array data
    filters: Object,    // Filter state
    template: Object,   // Template/appearance settings
});

const page = usePage();
const settings = computed(() => page.props.settings);
const colorScheme = computed(() => page.props.colorScheme);
</script>

<template>
    <TemplateWrapper
        :title="settings.social_title || settings.site_name"
        :description="settings.site_description"
        :keywords="settings.site_keywords"
        :social-image="settings.social_image"
        :color-scheme="colorScheme"
    >
        <!-- Page content here -->
    </TemplateWrapper>
</template>
```

## Routing

**Named routes with Ziggy:**
```js
// In template
<Link :href="route('frontend.home')">Home</Link>
<Link :href="route('frontend.products', { category: 'electronics' })">Products</Link>
<Link :href="route('frontend.product-detail', product.slug)">Detail</Link>

// In script
import { router } from "@inertiajs/vue3";

router.get(route('frontend.products'), { category: 'electronics' }, {
    preserveState: true,
    preserveScroll: true,
    only: ['products']
});

router.post(route('frontend.cart.store'), { product_id: id, quantity: 1 }, {
    preserveScroll: true
});
```

**Common route names in this project:**
- `frontend.home` — Homepage
- `frontend.products` — Product listing
- `frontend.product-detail` — Single product
- `frontend.cart.index` — Cart page
- `frontend.checkout.index` — Checkout
- `frontend.orders.index` / `frontend.orders.show` — Orders
- `frontend.blog.index` / `frontend.blog.show` — Blog
- `frontend.faq.index` — FAQ
- `frontend.flashsale.index` — Flash sale
- `frontend.voucher.index` — Vouchers
- `frontend.wishlist.index` — Wishlist
- `frontend.page.show` — CMS pages
- `frontend.account.profile` — User profile
- `frontend.auth.login` / `frontend.auth.register` — Auth

## Forms

**Traditional form submission with Inertia:**
```vue
<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("frontend.auth.login"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <form @submit.prevent="submit">
        <input v-model="form.email" type="email" />
        <span v-if="form.errors.email">{{ form.errors.email }}</span>
        
        <input v-model="form.password" type="password" />
        <span v-if="form.errors.password">{{ form.errors.password }}</span>
        
        <button type="submit" :disabled="form.processing">Login</button>
    </form>
</template>
```

**Async API calls (for partial updates):**
```js
import axios from "axios";

const response = await axios.post('/api/vouchers/apply', { code: voucherCode });
```

## Data Passing Patterns

**Controller to Frontend:**
```php
return Inertia::render('Home/Index', [
    'products' => ProductResource::collection($products),
    'categories' => Category::active()->get(),
    'flashsales' => Flashsale::active()->get(),
    'template' => $template,
]);
```

**Accessing shared props:**
```js
const page = usePage();
const settings = computed(() => page.props.settings);
const flash = computed(() => page.props.flash);
const auth = computed(() => page.props.auth);
```

## Lazy Loading / Partial Reloads

```js
router.get(route('frontend.products'), { 
    page: nextPage,
    category: selectedCategory 
}, {
    preserveState: true,
    preserveScroll: true,
    only: ['products']  // Only reload products data
});
```

## When to use me

- Creating new Inertia pages
- Adding navigation links
- Handling form submissions
- Implementing pagination or infinite scroll
- Working with Inertia shared data
- Redirecting or navigating programmatically

## Important Notes

- Always use `<script setup>` syntax
- Wrap pages with `<TemplateWrapper>` for consistent layout + SEO
- Use `route()` helper from Ziggy for all URLs
- For file uploads, use `useForm` with `form.post()`
- For real-time updates (cart, vouchers), use raw `axios` calls
- Flash messages from backend auto-appear as toasts via `FlashMessages.vue`
