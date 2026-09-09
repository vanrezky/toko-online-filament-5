<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { formatCurrency } from "../../lib/utils";

const props = defineProps({
    product: { type: Object, required: true },
});

const { locale } = useI18n();
const placeholder = "/images/placeholders/product-snapshot.svg";

const displayPrice = () => props.product.pricing?.final_price ?? props.product.sale_price ?? props.product.price;
const originalPrice = () => {
    const original = props.product.pricing?.original_price ?? props.product.price;

    return original > displayPrice() ? original : null;
};
const money = (amount) => formatCurrency(amount, locale.value === "id" ? "id-ID" : "en-US");

const fallbackImage = (event) => {
    event.target.onerror = null;
    event.target.src = placeholder;
};
</script>

<template>
    <article
        class="product-recommendation-card group relative min-w-0 overflow-hidden rounded-2xl border border-border bg-white p-2.5 transition-shadow hover:shadow-lg"
    >
        <Link
            :href="route('frontend.product-detail', product.slug)"
            class="product-recommendation-image block aspect-square overflow-hidden rounded-xl bg-secondary/45 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
        >
            <img
                :src="product.thumbnail || placeholder"
                :alt="product.name"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                @error="fallbackImage"
            />
        </Link>

        <div class="product-recommendation-copy mt-2">
            <Link
                :href="route('frontend.product-detail', product.slug)"
                class="product-recommendation-name text-foreground hover:text-primary block h-10 min-h-10 overflow-hidden text-xs leading-5 font-semibold"
                :style="{ display: '-webkit-box', WebkitBoxOrient: 'vertical', WebkitLineClamp: 2 }"
            >
                {{ product.name }}
            </Link>

            <div class="product-recommendation-price mt-2 min-w-0">
                <strong class="text-primary text-sm font-bold">{{ money(displayPrice()) }}</strong>
                <del v-if="originalPrice()" class="text-muted-foreground mt-1 block text-[11px]">{{ money(originalPrice()) }}</del>
            </div>
        </div>
    </article>
</template>
