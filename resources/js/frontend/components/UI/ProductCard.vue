<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { Heart, Star } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { formatCompactNumber, formatCurrency } from "../../lib/utils";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: "normal",
        validator: (value) => ["small", "normal", "large"].includes(value),
    },
});

const page = usePage();
const { t, locale } = useI18n();
const formatProductCount = (count) => formatCompactNumber(count, locale.value);

const isWishlisted = computed(() => {
    return page.props.wishlist_product_ids?.includes(props.product.id);
});

const pricing = computed(() => props.product.pricing ?? null);

const isSale = computed(() => (pricing.value?.discount ?? 0) > 0);

const displayPrice = computed(() => {
    return pricing.value?.final_price ?? props.product.sale_price ?? props.product.price;
});

const originalPrice = computed(() => {
    if (isSale.value) {
        return pricing.value?.original_price ?? props.product.price;
    }
    return null;
});

const discountPercentage = computed(() => {
    if (isSale.value && originalPrice.value) {
        return Math.round((1 - displayPrice.value / originalPrice.value) * 100);
    }
    return null;
});

const badge = computed(() => {
    if (isSale.value) {
        const discount = pricing.value?.flashsale?.discount_percentage ?? discountPercentage.value;
        return { text: `${discount}%`, class: "bg-destructive text-white" };
    }
    if (props.product.is_new) {
        return { text: t("labels.product.new"), class: "bg-primary text-primary-foreground" };
    }
    if (props.product.is_featured || props.product.is_best_seller) {
        return { text: t("labels.product.best_seller"), class: "bg-amber-700 text-white" };
    }
    return null;
});

const truncatedProductName = computed(() => {
    const name = props.product.name || "";
    return name.length > 22 ? `${name.slice(0, 22)} ...` : name;
});

const toggleWishlist = (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (!page.props.auth.user) {
        router.get(route("frontend.login"));
        return;
    }

    router.post(
        route("frontend.wishlist.toggle"),
        {
            product_id: props.product.id,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                if (errors?.redirect) {
                    window.location.href = errors.redirect;
                }
            },
        },
    );
};

const sizeClasses = computed(() => {
    switch (props.size) {
        case "small":
            return "w-40";
        case "large":
            return "w-56";
        default:
            return "";
    }
});
</script>

<template>
    <article>
        <Link
            :href="route('frontend.product-detail', product.slug)"
            class="group border-border bg-background hover:border-primary/60 focus-visible:ring-primary relative flex flex-col overflow-hidden rounded-lg border transition-[border-color,box-shadow,transform] duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_14px_28px_-18px_hsl(var(--foreground)/0.48)] focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transform-none motion-reduce:transition-none"
            :class="sizeClasses"
        >
            <!-- Image Container -->
            <div class="bg-secondary relative aspect-square overflow-hidden">
                <!-- Product Image -->
                <img
                    v-if="product.thumbnail"
                    :src="product.thumbnail"
                    :alt="product.name"
                    class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.06] motion-reduce:transition-none"
                />

                <!-- No Image Placeholder -->
                <div v-else class="bg-muted text-muted-foreground flex h-full w-full flex-col items-center justify-center gap-2" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                        />
                    </svg>
                    <span class="text-xs font-medium">{{ t("labels.product.no_image") }}</span>
                </div>

                <div v-if="badge" class="absolute top-3 left-3 px-2.5 py-1 text-xs font-extrabold tracking-wide shadow-sm" :class="badge.class">
                    {{ badge.text }}
                </div>

                <!-- Wishlist Button -->
                <Button
                    @click="toggleWishlist"
                    :icon="Heart"
                    :icon-props="{ fill: isWishlisted ? 'currentColor' : 'none' }"
                    size="icon"
                    class="bg-background/95 hover:bg-background focus-visible:ring-primary absolute top-3 right-3 z-10 h-9 w-9 rounded-full p-0 shadow-[0_6px_14px_-8px_hsl(var(--foreground)/0.55)] transition-[background-color,transform] duration-200 hover:scale-105 focus-visible:ring-2 motion-reduce:transform-none motion-reduce:transition-none"
                    :class="isWishlisted ? 'text-destructive opacity-100' : 'text-muted-foreground opacity-100'"
                    :aria-label="t('labels.product.save_to_wishlist')"
                />
            </div>

            <!-- Content Section -->
            <div class="flex grow flex-col p-3 sm:p-4">
                <!-- <div v-if="product.category_name" class="text-primary mb-2 inline-block self-start text-[11px] font-bold tracking-[0.12em] uppercase">
                    {{ product.category_name }}
                </div> -->
                <!-- Product Name -->
                <h3
                    class="text-foreground group-hover:text-primary mb-2 line-clamp-2 min-h-[2.5rem] text-sm font-bold leading-snug tracking-[-0.02em] transition-colors duration-200 sm:min-h-[2.75rem] sm:text-[1.0625rem]"
                >
                    {{ truncatedProductName }}
                </h3>

                <!-- Price & Cart Section -->
                <div class="mt-auto flex items-end justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <span class="text-foreground text-lg font-extrabold leading-none tracking-[-0.035em] sm:text-xl">
                            {{ formatCurrency(displayPrice) }}
                        </span>
                        <p v-if="isSale" class="text-primary mt-2 text-xs font-extrabold tracking-[-0.01em]">
                            {{ t("labels.product.save_amount", { amount: formatCurrency(originalPrice - displayPrice) }) }}
                        </p>
                    </div>
                </div>
                <div class="text-muted-foreground border-border mt-4 flex items-center justify-between gap-2 border-t pt-3 text-xs">
                    <span class="flex items-center gap-1 font-bold text-amber-700">
                        <Star class="h-3.5 w-3.5 fill-current" />
                        {{ product.rating_average?.toFixed?.(1) || "0.0" }}
                        <span class="text-muted-foreground">({{ formatProductCount(product.review_count) }})</span>
                    </span>
                    <span class="font-semibold">{{ formatProductCount(product.sold_count) }} {{ t("labels.product.sold") }}</span>
                </div>
            </div>
        </Link>
    </article>
</template>
