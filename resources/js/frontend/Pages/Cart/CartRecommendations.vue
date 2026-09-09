<script setup>
import ProductRecommendationCard from "../../components/UI/ProductRecommendationCard.vue";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ArrowRight } from "lucide-vue-next";

const { t } = useI18n();
defineProps({ recommendations: { type: Array, default: () => [] } });
</script>

<template>
    <section
        v-if="recommendations.length"
        class="cart-recommendations min-w-0 lg:col-start-1 lg:row-start-2"
        :aria-label="t('labels.cart.ui.recommendations')"
    >
        <div class="cart-recommendation-heading mb-2 flex items-center justify-between gap-3">
            <h2 class="text-foreground text-xl font-bold tracking-tight">{{ t("labels.cart.ui.recommendations") }}</h2>
            <Link
                class="text-primary inline-flex items-center gap-2 text-xs whitespace-nowrap hover:underline hover:underline-offset-4"
                :href="route('frontend.products')"
                >{{ t("labels.cart.ui.view_all") }}<ArrowRight class="h-4 w-4" aria-hidden="true"
            /></Link>
        </div>
        <div
            class="cart-recommendation-grid scrollbar-hidden flex touch-pan-x snap-x snap-mandatory gap-3 overflow-x-auto overscroll-x-contain pb-2 sm:gap-4 md:gap-5 lg:grid lg:grid-cols-4 lg:gap-3 lg:overflow-visible lg:pb-0"
        >
            <ProductRecommendationCard
                v-for="product in recommendations"
                :key="product.id"
                :product="product"
                class="cart-recommendation-card w-[min(40vw,10rem)] shrink-0 snap-start sm:w-40 md:w-44 lg:w-auto lg:max-w-[10rem] lg:shrink lg:justify-self-center"
            />
        </div>
    </section>
</template>
