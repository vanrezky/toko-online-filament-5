<script setup>
import { computed, ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import { ChevronRight } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";
import ProductCard from "./ProductCard.vue";

const props = defineProps({
    products: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
    template: { type: Object, default: null },
});

const { t } = useI18n();
const allProducts = ref([...(props.products?.data || [])]);
const title = computed(() => props.filters?.category || getSectionContent(props.template, "products_grid", "title", "Semua Produk"));
const subtitle = computed(() => getSectionContent(props.template, "products_grid", "subtitle", "Jelajahi koleksi lengkap produk kami"));
const limit = computed(() => Math.max(1, Number(getSectionContent(props.template, "products_grid", "limit", 10)) || 10));
const configuredColumns = computed(() => getSectionContent(props.template, "products_grid", "columns", null));
const showLoadMore = computed(() => {
    const value = getSectionContent(props.template, "products_grid", "show_load_more", "1");

    return ![false, 0, "0", "false"].includes(value);
});
const columns = computed(() => {
    const value = Number(getSectionContent(props.template, "products_grid", "columns", 4));

    return [2, 3, 4, 5].includes(value) ? value : 4;
});
const columnClasses = computed(() => ({
    "lg:grid-cols-2": Boolean(configuredColumns.value) && columns.value === 2,
    "lg:grid-cols-3": Boolean(configuredColumns.value) && columns.value === 3,
    "lg:grid-cols-4": Boolean(configuredColumns.value) && columns.value === 4,
    "lg:grid-cols-5": Boolean(configuredColumns.value) && columns.value === 5,
    "xl:grid-cols-2": Boolean(configuredColumns.value) && columns.value === 2,
    "xl:grid-cols-3": Boolean(configuredColumns.value) && columns.value === 3,
    "xl:grid-cols-4": Boolean(configuredColumns.value) && columns.value === 4,
    "xl:grid-cols-5": Boolean(configuredColumns.value) && columns.value === 5,
}));
const visibleProducts = computed(() => allProducts.value.slice(0, limit.value));

watch(
    () => props.products?.data,
    (products) => {
        allProducts.value = [...(products || [])];
    },
);
</script>

<template>
    <section class="py-10 md:py-14">
        <div class="container mx-auto px-4 md:px-8">
            <div class="mb-6 flex items-end justify-between gap-4 md:mb-8">
                <div>
                    <h2 class="text-foreground text-2xl font-bold tracking-[-0.04em] md:text-3xl">{{ title }}</h2>
                    <p v-if="!filters?.category" class="text-muted-foreground mt-1 text-sm">{{ subtitle }}</p>
                </div>
                <Link
                    v-if="showLoadMore"
                    :href="route('frontend.products')"
                    class="text-primary hover:text-primary/75 focus-visible:ring-primary inline-flex min-h-11 items-center gap-1 rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none sm:text-sm"
                >
                    <span class="hidden sm:inline">{{ t("labels.actions.view_all_products") }}</span>
                    <span class="sm:hidden">{{ t("labels.actions.view_all") }}</span>
                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>

            <div v-if="visibleProducts.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6" :class="columnClasses">
                <ProductCard v-for="product in visibleProducts" :key="product.uuid || product.id" :product="product" />
            </div>

            <div v-else class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-50 to-slate-100 py-20 text-center">
                <div class="bg-primary/5 absolute -top-10 -right-10 h-40 w-40 rounded-full"></div>
                <div class="bg-primary/5 absolute -bottom-10 -left-10 h-32 w-32 rounded-full"></div>

                <div class="relative z-10">
                    <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </div>
                    <h3 class="text-foreground mb-2 text-xl font-bold">{{ t("labels.products.empty_title") }}</h3>
                    <p class="text-muted-foreground mx-auto mb-8 max-w-sm text-sm">{{ t("labels.products.empty_description") }}</p>
                    <Link :href="route('frontend.home')" class="from-primary to-primary/90 text-primary-foreground shadow-primary/30 hover:shadow-primary/40 inline-flex items-center gap-2 rounded-full bg-gradient-to-r px-8 py-3 text-sm font-semibold shadow-lg transition-all duration-300 hover:shadow-xl">
                        {{ t("labels.actions.back_to_home") }}
                        <ChevronRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>

        </div>
    </section>
</template>
