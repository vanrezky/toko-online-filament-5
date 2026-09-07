<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { Heart, ShoppingCart, Star } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { formatCompactNumber, formatCurrency } from "../../lib/utils";

const props = defineProps({
    product: { type: Object, required: true },
    size: {
        type: String,
        default: "normal",
        validator: (value) => ["small", "normal", "large"].includes(value),
    },
});

const page = usePage();
const { t, locale } = useI18n();
const formatProductCount = (count) => formatCompactNumber(count, locale.value);
const pricing = computed(() => props.product.pricing ?? null);
const isWishlisted = computed(() => page.props.wishlist_product_ids?.includes(props.product.id));
const isSale = computed(() => (pricing.value?.discount ?? 0) > 0);
const displayPrice = computed(() => pricing.value?.final_price ?? props.product.sale_price ?? props.product.price);
const originalPrice = computed(() => (isSale.value ? pricing.value?.original_price ?? props.product.price : null));
const discountPercentage = computed(() => {
    if (!isSale.value || !originalPrice.value) return null;

    return Math.round((1 - displayPrice.value / originalPrice.value) * 100);
});
const badge = computed(() => {
    if (isSale.value) {
        const discount = pricing.value?.flashsale?.discount_percentage ?? discountPercentage.value;

        return { text: `${discount}%` };
    }
    if (props.product.is_new) return { text: t("labels.product.new") };
    if (props.product.is_featured || props.product.is_best_seller) return { text: t("labels.product.best_seller") };

    return null;
});
const truncatedProductName = computed(() => {
    const name = props.product.name || "";

    return name.length > 60 ? `${name.slice(0, 60).trimEnd()}…` : name;
});
const sizeClasses = computed(() => ({
    "w-40": props.size === "small",
    "w-56": props.size === "large",
}));

const toggleWishlist = (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (!page.props.auth?.user) {
        router.get(route("frontend.login"));
        return;
    }

    router.post(
        route("frontend.wishlist.toggle"),
        { product_id: props.product.id },
        {
            preserveScroll: true,
            onError: (errors) => {
                if (errors?.redirect) window.location.href = errors.redirect;
            },
        },
    );
};
</script>

<template>
    <article>
        <Link
            :href="route('frontend.product-detail', product.slug)"
            class="group border-border bg-background hover:border-primary/50 focus-visible:ring-primary relative flex h-full flex-col overflow-hidden rounded-xl border transition-[border-color,box-shadow] duration-300 ease-out hover:shadow-[0_18px_32px_-24px_hsl(var(--foreground)/0.6)] focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transition-none"
            :class="sizeClasses"
        >
            <div
                class="relative aspect-square overflow-hidden"
                :class="product.thumbnail ? 'bg-transparent p-1.5 sm:p-2' : 'bg-secondary/35 p-1.5 sm:p-2'"
            >
                <div
                    class="relative h-full w-full overflow-hidden"
                    :class="product.thumbnail ? 'rounded-lg bg-transparent' : 'rounded-lg bg-secondary'"
                >
                    <img
                        v-if="product.thumbnail"
                        :src="product.thumbnail"
                        :alt="product.name"
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-contain transition-transform duration-500 ease-out group-hover:scale-[1.05] motion-reduce:transition-none"
                        :class="product.thumbnail ? 'rounded-lg p-0' : 'p-2 sm:p-3'"
                    />
                    <div v-else class="text-muted-foreground flex h-full w-full flex-col items-center justify-center gap-2" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                        </svg>
                        <span class="text-[10px] font-medium">{{ t("labels.product.no_image") }}</span>
                    </div>
                </div>

                <span
                    v-if="badge"
                    class="absolute top-3 left-3 z-10 rounded-full px-2.5 py-1 text-[10px] font-bold shadow-sm"
                    :class="isSale ? 'bg-destructive text-destructive-foreground' : product.is_new ? 'bg-emerald-600 text-white' : 'bg-primary text-primary-foreground'"
                >
                    {{ badge.text }}
                </span>

                <Button
                    @click="toggleWishlist"
                    :icon="Heart"
                    :icon-props="{ fill: isWishlisted ? 'currentColor' : 'none' }"
                    size="icon"
                    class="bg-background/95 hover:bg-background focus-visible:ring-primary absolute top-3 right-3 z-10 h-8 w-8 rounded-full p-0 shadow-[0_8px_16px_-12px_hsl(var(--foreground)/0.7)] transition-transform hover:scale-105 focus-visible:ring-2 motion-reduce:transform-none sm:h-9 sm:w-9"
                    :class="isWishlisted ? 'text-destructive' : 'text-foreground'"
                    :aria-label="isWishlisted ? t('labels.product.saved_to_wishlist') : t('labels.product.save_to_wishlist')"
                />
            </div>

            <div class="flex grow flex-col p-3 sm:p-3.5">
                <h3 class="text-foreground group-hover:text-primary line-clamp-2 min-h-[2.5rem] text-xs leading-5 font-semibold tracking-[-0.02em] transition-colors sm:text-sm">
                    {{ truncatedProductName }}
                </h3>

                <div class="text-muted-foreground mt-2 flex items-center gap-1 text-[11px]">
                    <span class="text-primary flex items-center gap-1 font-semibold">
                        <Star class="h-3.5 w-3.5 fill-current" aria-hidden="true" />
                        {{ product.rating_average?.toFixed?.(1) || "0.0" }}
                    </span>
                    <span>({{ formatProductCount(product.review_count) }})</span>
                </div>

                <div class="border-border mt-3 flex items-end justify-between gap-2 border-t pt-3">
                    <div class="min-w-0">
                        <p class="text-primary text-sm leading-none font-bold tracking-[-0.025em] sm:text-base">{{ formatCurrency(displayPrice) }}</p>
                        <del v-if="originalPrice" class="text-muted-foreground mt-1 block text-[11px]">{{ formatCurrency(originalPrice) }}</del>
                    </div>
                    <span class="border-primary/45 text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border sm:h-9 sm:w-9" aria-hidden="true">
                        <ShoppingCart class="h-4 w-4" />
                    </span>
                </div>
            </div>
        </Link>
    </article>
</template>
