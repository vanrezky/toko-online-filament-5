---
name: fe-pages
description: Create Inertia.js pages for this e-commerce project. Follow the page structure, layout wrapping, and data handling patterns.
license: MIT
compatibility: opencode
metadata:
  category: frontend
  type: pages
---

## What I do

I help create new Inertia.js pages following the project's established patterns.

## Page File Location

Pages live in `resources/js/frontend/pages/` with directory nesting matching the route:
- Route `frontend.blog.index` → `pages/Blog/Index.vue`
- Route `frontend.product-detail` → `pages/ProductDetail/Index.vue`
- Route `frontend.orders.show` → `pages/Orders/Show.vue`

## Page Boilerplate

```vue
<script setup>
import { computed, ref, watch } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";

const props = defineProps({
    // Data from controller
    items: Object,
    filters: Object,
    template: Object,
});

const page = usePage();
const settings = computed(() => page.props.settings);
const colorScheme = computed(() => page.props.colorScheme);
</script>

<template>
    <TemplateWrapper
        :title="pageTitle"
        :description="pageDescription"
        :keywords="pageKeywords"
        :social-image="settings.social_image"
        :color-scheme="colorScheme"
    >
        <!-- Page sections -->
    </TemplateWrapper>
</template>
```

## Page Structure Patterns

### Listing Page (with filters/pagination)
```vue
<script setup>
const props = defineProps({
    products: Object,  // Paginated
    categories: Array,
    filters: Object,
});

const allItems = ref([...(props.products?.data || [])]);

watch(() => props.products?.data, (newData) => {
    allItems.value = [...newData];
});
</script>

<template>
    <TemplateWrapper ...>
        <section class="py-8 md:py-12">
            <div class="container mx-auto px-4">
                <!-- Page header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">Page Title</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Subtitle</p>
                    </div>
                </div>
                
                <!-- Grid -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <ProductCard v-for="item in allItems" :key="item.id" :product="item" />
                </div>
                
                <!-- Empty state -->
                <div v-if="allItems.length === 0" class="...">No items found</div>
                
                <!-- Pagination / Load more -->
                <div class="flex justify-center pt-12">
                    <Link :href="route('frontend.products')" class="...">Load More</Link>
                </div>
            </div>
        </section>
    </TemplateWrapper>
</template>
```

### Detail Page
```vue
<script setup>
const props = defineProps({
    product: Object,
    relatedProducts: Array,
});
</script>

<template>
    <TemplateWrapper ...>
        <!-- Breadcrumb -->
        <section class="py-4">
            <div class="container mx-auto px-4">
                <div class="text-sm text-muted-foreground">
                    <Link :href="route('frontend.home')">Home</Link>
                    <span class="mx-2">/</span>
                    <Link :href="route('frontend.products')">Products</Link>
                    <span class="mx-2">/</span>
                    <span class="text-foreground">{{ product.name }}</span>
                </div>
            </div>
        </section>
        
        <!-- Main content -->
        <section class="py-8">
            <div class="container mx-auto px-4">
                <div class="grid gap-8 md:grid-cols-2">
                    <!-- Left: Images -->
                    <!-- Right: Details -->
                </div>
            </div>
        </section>
        
        <!-- Related section -->
        <section class="py-8">
            <div class="container mx-auto px-4">
                <h2 class="text-xl font-bold">Related Products</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
                </div>
            </div>
        </section>
    </TemplateWrapper>
</template>
```

### Form Page
```vue
<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    message: "",
});

const submit = () => {
    form.post(route("frontend.contact.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <TemplateWrapper ...>
        <section class="py-12">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl">
                    <h1 class="text-2xl font-bold">Contact Us</h1>
                    
                    <form @submit.prevent="submit" class="mt-6 space-y-4">
                        <div>
                            <label class="text-sm font-medium">Name</label>
                            <input v-model="form.name" class="input-elegant mt-1" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        
                        <button type="submit" :disabled="form.processing" class="btn-primary w-full">
                            Submit
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </TemplateWrapper>
</template>
```

## Existing Pages (Don't Duplicate)

| Page | Route | Purpose |
|------|-------|---------|
| `Home/Index.vue` | `frontend.home` | Homepage with sections |
| `Products/Index.vue` | `frontend.products` | Product listing |
| `ProductDetail/Index.vue` | `frontend.product-detail` | Single product |
| `Cart/Index.vue` | `frontend.cart.index` | Shopping cart |
| `Checkout/Index.vue` | `frontend.checkout.index` | Checkout flow |
| `Orders/Index.vue` | `frontend.orders.index` | Order history |
| `Orders/Show.vue` | `frontend.orders.show` | Order detail |
| `Blog/Index.vue` | `frontend.blog.index` | Blog listing |
| `Blog/Show.vue` | `frontend.blog.show` | Blog post |
| `Faq/Index.vue` | `frontend.faq.index` | FAQ page |
| `Flashsale/Index.vue` | `frontend.flashsale.index` | Flash sales |
| `Voucher/Index.vue` | `frontend.voucher.index` | Vouchers |
| `Wishlist/Index.vue` | `frontend.wishlist.index` | Wishlist |
| `Page/Show.vue` | `frontend.page.show` | CMS pages |
| `Account/Profile.vue` | `frontend.account.profile` | User profile |
| `Auth/Login.vue` | `frontend.auth.login` | Login |
| `Auth/Register.vue` | `frontend.auth.register` | Register |
| `Auth/ForgotPassword.vue` | `frontend.auth.forgot-password` | Forgot password |
| `Auth/ResetPassword.vue` | `frontend.auth.reset-password` | Reset password |

## Template Section Helper

For template-based content (homepage sections managed via admin):

```js
const getSectionContent = (sectionType, key, defaultValue = "") => {
    if (!props.template?.sections) return defaultValue;
    const section = props.template.sections.find((s) => s.type === sectionType);
    return section?.contents?.[key] || defaultValue;
};
```

## When to use me

- Creating new frontend pages
- Adding new routes with Inertia views
- Structuring page layouts
- Handling page-level data

## Important Notes

- Always wrap pages with `<TemplateWrapper>`
- Use `defineProps` for controller-passed data
- Use `usePage()` for shared data (settings, auth, flash)
- Use `useForm()` for form submissions
- Use `Link` component for internal navigation
- Use `route()` from Ziggy for URL generation
