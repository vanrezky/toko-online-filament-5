---
name: fe-templates
description: Create or modify layout templates and template section components. Handle BaseLayout, Header, Footer, TopbarNotice, and template wrapper components.
license: MIT
compatibility: opencode
metadata:
  category: frontend
  type: templates
---

## What I do

I help create or modify layout templates and template section components.

## Layout Architecture

This project has a **dual layout system**:

### 1. Desktop Default Layout (TemplateWrapper → BaseLayout)
```
TemplateWrapper.vue
  └─ BaseLayout.vue
       ├─ AppHead.vue (SEO meta)
       ├─ Toaster (vue-sonner)
       ├─ FlashMessages.vue
       ├─ vue3-confirm-dialog
       ├─ TopbarNotice.vue
       ├─ Header.vue
       ├─ <main><slot /></main>
       └─ Footer.vue
```

### 2. Mobile Layout (Layout.vue)
- `max-w-[450px]` constrained
- Mobile app-shell style
- Same toast/flash/confirm components

**Current behavior:** `TemplateWrapper` hardcodes `DefaultLayout` always.

## BaseLayout.vue Structure

```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppHead from '../../AppHead.vue';
import TopbarNotice from './TopbarNotice.vue';
import Header from './Header.vue';
import Footer from './Footer.vue';
import { Toaster } from "vue-sonner";
import FlashMessages from "@frontend/components/FlashMessages.vue";

const props = defineProps({
  title: String,
  description: String,
  keywords: String,
  socialImage: String,
});

const settings = computed(() => usePage().props.settings);
</script>

<template>
  <AppHead :title="title" :description="description" :keywords="keywords" :social-image="socialImage" />
  <Toaster position="top-right" richColors closeButton />
  <FlashMessages />
  <vue3-confirm-dialog />
  
  <div class="min-h-screen flex flex-col bg-white">
    <TopbarNotice />
    <Header />
    <main class="flex-grow">
      <slot />
    </main>
    <Footer />
  </div>
</template>
```

## Template Section Components (Dynamic Homepage Sections)

Template sections are managed via admin and rendered based on `template.sections`:

**Available section types:**
- `hero` — Hero banner
- `featured_products` — Featured product grid
- `flash_sale` — Flash sale countdown
- `products_grid` — All products grid
- `newsletter` — Email subscription

**Section content helper:**
```js
const getSectionContent = (sectionType, key, defaultValue = "") => {
    if (!props.template?.sections) return defaultValue;
    const section = props.template.sections.find((s) => s.type === sectionType);
    return section?.contents?.[key] || defaultValue;
};
```

## Creating a New Template Section Component

Location: `resources/js/frontend/components/UI/SectionName.vue`

```vue
<script setup>
const props = defineProps({
    title: String,
    subtitle: String,
    items: Array,
});
</script>

<template>
    <section class="py-8 md:py-12">
        <div class="container mx-auto px-4">
            <!-- Section header -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-foreground md:text-2xl">{{ title }}</h2>
                <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
            </div>
            
            <!-- Content -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <slot />
            </div>
        </div>
    </section>
</template>
```

## Existing Template Components (Don't Duplicate)

| Component | Location | Purpose |
|-----------|----------|---------|
| `TemplateWrapper.vue` | `components/` | Layout selector + SEO + color scheme |
| `BaseLayout.vue` | `components/Templates/Default/` | Desktop layout shell |
| `Header.vue` | `components/Templates/Default/` | Site header with nav |
| `Footer.vue` | `components/Templates/Default/` | Site footer |
| `TopbarNotice.vue` | `components/Templates/Default/` | Announcement bar |
| `Layout.vue` | `components/` | Mobile layout shell |
| `AppHead.vue` | `components/` | SEO meta tags |

## AppHead.vue (SEO)

Handles Open Graph, Twitter Cards, CSP, dynamic title:

```vue
<AppHead
    :title="pageTitle"
    :description="pageDescription"
    :keywords="pageKeywords"
    :social-image="settings.social_image"
/>
```

## When to use me

- Creating new layout templates
- Modifying Header/Footer/TopbarNotice
- Adding new homepage section types
- Working with template-based CMS content
- Changing layout structure

## Important Notes

- `TemplateWrapper` is the entry point for all pages
- `BaseLayout` should NOT be imported directly by pages
- Template sections receive data from `template.sections` prop
- Color scheme is applied via `useColorScheme()` composable
- SEO data comes from `page.props.settings`
