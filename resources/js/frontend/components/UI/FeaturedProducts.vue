<script setup>
import { computed } from "vue";
import ProductCard from "./ProductCard.vue";
import { ChevronRight } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";

const props = defineProps({
    products: { type: [Array, Object], default: () => [] },
    template: { type: Object, default: null },
});

const { t } = useI18n();
const limit = computed(() => Math.max(1, Number(getSectionContent(props.template, "featured_products", "limit", 6)) || 6));
const showDiscount = computed(() => {
    const value = getSectionContent(props.template, "featured_products", "show_discount", "1");

    return ![false, 0, "0", "false"].includes(value);
});
const featuredProducts = computed(() => (Array.isArray(props.products) ? props.products : props.products?.data || []).slice(0, limit.value));
const sectionTitle = computed(() => getSectionContent(props.template, "featured_products", "title", "Pilihan Terbaik"));
const sectionSubtitle = computed(() => getSectionContent(props.template, "featured_products", "subtitle", "Produk pilihan dengan kualitas terbaik untuk Anda"));
</script>

<template>
    <section v-if="featuredProducts.length" class="bg-background py-6 md:py-10" aria-labelledby="featured-products-title">
        <div class="container mx-auto px-4 md:px-8">
            <div class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h2 id="featured-products-title" class="text-foreground text-2xl font-bold tracking-[-0.04em]">{{ sectionTitle }}</h2>
                    <p v-if="sectionSubtitle" class="text-muted-foreground mt-1 text-sm">{{ sectionSubtitle }}</p>
                </div>
                <Link
                    :href="route('frontend.products')"
                    class="text-primary hover:text-primary/75 focus-visible:ring-primary inline-flex min-h-11 items-center gap-1 rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none sm:text-sm"
                >
                    <span class="hidden sm:inline">{{ t("labels.home.featured_view_all") }}</span>
                    <span class="sm:hidden">{{ t("labels.actions.view_all") }}</span>
                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6">
                <div v-for="product in featuredProducts" :key="product.uuid || product.id" class="min-w-0">
                    <ProductCard :product="product" :show-discount="showDiscount" />
                </div>
            </div>
        </div>
    </section>
</template>
