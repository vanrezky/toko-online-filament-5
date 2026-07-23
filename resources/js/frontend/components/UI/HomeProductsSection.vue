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

watch(
    () => props.products?.data,
    (products) => {
        allProducts.value = [...(products || [])];
    },
);
</script>

<template>
    <section class="py-10 md:py-14">
        <div class="container mx-auto px-4">
            <div class="mb-8 flex items-center justify-between md:mb-10">
                <div>
                    <h2 class="text-foreground text-xl font-bold md:text-2xl">{{ title }}</h2>
                    <p v-if="!filters?.category" class="text-muted-foreground mt-1 text-sm">{{ subtitle }}</p>
                </div>
            </div>

            <div v-if="allProducts.length > 0" class="grid grid-cols-2 gap-x-3 gap-y-6 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 md:gap-5 lg:grid-cols-5">
                <ProductCard v-for="product in allProducts" :key="product.uuid || product.id" :product="product" />
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

            <div v-if="allProducts.length > 0" class="flex justify-center pt-8 md:pt-10">
                <Link :href="route('frontend.products')" class="bg-secondary text-foreground hover:bg-primary hover:text-primary-foreground inline-flex items-center gap-2 rounded-full px-8 py-3 text-sm font-semibold transition-colors">
                    {{ t("labels.actions.view_more") }}
                    <ChevronRight class="h-4 w-4" />
                </Link>
            </div>
        </div>
    </section>
</template>
