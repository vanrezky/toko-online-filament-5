<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TemplateWrapper from '../../components/TemplateWrapper.vue';
import ProductCard from '../../components/UI/ProductCard.vue';
import AccountShell from '../../components/Account/AccountShell.vue';
import { Heart, ShoppingBag, ArrowRight } from 'lucide-vue-next';
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
  products: [Object, Array]
});

const items = computed(() => {
  if (Array.isArray(props.products)) {
    return props.products;
  }
  return props.products?.data || [];
});
</script>

<template>
    <TemplateWrapper :title="t('meta.wishlist.title')">
        <div class="py-12">
            <div class="container mx-auto px-4 md:px-6">
                <AccountShell active-destination="wishlist">
                    <div class="space-y-12">
                        <header class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                            <div class="space-y-2">
                                <h1 class="text-foreground text-3xl font-bold tracking-tight md:text-4xl">{{ t('labels.wishlist.heading') }}</h1>
                                <p class="text-muted-foreground text-sm">{{ t('labels.wishlist.description') }}</p>
                            </div>
                            <span v-if="items.length > 0" class="text-muted-foreground text-xs font-bold uppercase tracking-widest">{{ t('labels.wishlist.item_count', { count: items.length }) }}</span>
                        </header>
                        <div v-if="items.length > 0" class="grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-8 lg:grid-cols-4">
                            <ProductCard v-for="product in items" :key="product.uuid" :product="product" />
                        </div>
                        <div v-else class="border-border space-y-8 rounded-2xl border bg-background py-20 text-center shadow-sm">
                            <div class="bg-secondary mx-auto flex h-24 w-24 items-center justify-center rounded-full"><Heart class="text-muted-foreground h-10 w-10" /></div>
                            <div class="space-y-3"><h2 class="text-foreground text-2xl font-bold">{{ t('labels.wishlist.empty_title') }}</h2><p class="text-muted-foreground mx-auto max-w-sm text-sm">{{ t('labels.wishlist.empty_description') }}</p></div>
                            <Link :href="route('frontend.products')" class="bg-primary text-primary-foreground inline-flex rounded-xl px-6 py-3 text-sm font-semibold transition-colors hover:bg-primary/90">{{ t('labels.actions.explore_products') }}</Link>
                        </div>
                    </div>
                </AccountShell>
            </div>
        </div>
    </TemplateWrapper>
</template>
