<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ArrowRight, Heart, ShoppingCart, Star } from "lucide-vue-next";
import { formatCurrency } from "../../lib/utils";

const { t, locale } = useI18n();
const saved = ref([]);
defineProps({ recommendations: { type: Array, default: () => [] } });
const toggleSaved = (key) => {
    saved.value = saved.value.includes(key) ? saved.value.filter((id) => id !== key) : [...saved.value, key];
};
const money = (price) => formatCurrency(price, locale.value === "id" ? "id-ID" : "en-US");
const finalPrice = (product) => product.pricing?.final_price ?? product.price;
const originalPrice = (product) => product.pricing?.original_price ?? product.price;
const reviewCount = (count) =>
    new Intl.NumberFormat(locale.value === "id" ? "id-ID" : "en-US", {
        notation: count >= 1000 ? "compact" : "standard",
        maximumFractionDigits: 1,
    }).format(count);
const fallback = (event) => {
    event.target.onerror = null;
    event.target.src = "/images/placeholders/product-snapshot.svg";
};
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
            class="cart-recommendation-grid scrollbar-hidden grid auto-cols-[8rem] grid-flow-col gap-2 overflow-x-auto pb-1 md:auto-cols-auto md:grid-flow-row md:grid-cols-5 md:overflow-visible md:pb-0"
        >
            <article
                v-for="product in recommendations"
                :key="product.id"
                class="cart-recommendation border-border relative min-w-0 overflow-hidden rounded-xl border bg-white"
            >
                <Link
                    :href="route('frontend.product-detail', product.slug)"
                    class="cart-recommendation-image bg-secondary/60 m-1 block h-24 overflow-hidden rounded-lg md:h-20 xl:h-24"
                    ><img
                        class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                        :src="product.thumbnail || '/images/placeholders/product-snapshot.svg'"
                        :alt="product.name"
                        loading="lazy"
                        @error="fallback"
                /></Link>
                <button
                    class="cart-recommendation-save text-foreground hover:text-primary absolute top-1.5 right-1.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/90 shadow-sm transition-colors"
                    :aria-label="t('labels.cart.ui.save_product', { name: product.name })"
                    :aria-pressed="saved.includes(product.id)"
                    @click="toggleSaved(product.id)"
                >
                    <Heart class="h-4 w-4" :fill="saved.includes(product.id) ? 'currentColor' : 'none'" aria-hidden="true" />
                </button>
                <div class="cart-recommendation-copy relative px-2 pt-1 pb-3">
                    <Link
                        class="cart-recommendation-name text-foreground hover:text-primary block truncate text-[11px] font-semibold"
                        :href="route('frontend.product-detail', product.slug)"
                        >{{ product.name }}</Link
                    ><span class="cart-rating text-muted-foreground my-1 flex items-center gap-1 text-[10px]"
                        ><Star class="h-3 w-3 fill-amber-500 text-amber-500" aria-hidden="true" />{{ product.rating_average }} ({{
                            reviewCount(product.review_count)
                        }})</span
                    >
                    <div class="cart-recommendation-price flex flex-wrap gap-x-1.5 pr-7 text-[10px]">
                        <strong class="text-primary text-xs font-semibold">{{ money(finalPrice(product)) }}</strong
                        ><del v-if="originalPrice(product) > finalPrice(product)" class="text-muted-foreground">{{
                            money(originalPrice(product))
                        }}</del>
                    </div>
                    <Link
                        class="cart-recommendation-shop border-primary text-primary hover:bg-primary hover:text-primary-foreground absolute right-2 bottom-2 inline-flex h-8 w-8 items-center justify-center rounded-lg border transition-colors"
                        :href="route('frontend.product-detail', product.slug)"
                        :aria-label="t('labels.cart.ui.view_product', { name: product.name })"
                        ><ShoppingCart class="h-4 w-4" aria-hidden="true"
                    /></Link>
                </div>
            </article>
        </div>
    </section>
</template>
