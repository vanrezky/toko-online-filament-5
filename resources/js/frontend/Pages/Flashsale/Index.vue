<script setup>
/**
 * THESIS: A time-bound product ledger makes the active drop legible without marketplace noise.
 * OWN-WORLD: Warm semantic tokens, an ink-dark deadline panel, precise borders, and editorial spacing.
 * STORY: Visitors see the remaining window, understand the offer, then browse every eligible product.
 * FIRST VIEWPORT: Countdown ledger on the left, active-drop details on the right, products immediately below.
 * FORM: The Drop Ledger, a deliberate asymmetrical catalog rather than a generic promotional hero.
 */
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref } from "vue";
import { ArrowRight, Clock3, Flame, PackageOpen, ShoppingBag, Sparkles } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import ProductCard from "../../components/UI/ProductCard.vue";

const props = defineProps({
    flashsale: {
        type: Object,
        default: null,
    },
    products: {
        type: Object,
        default: null,
    },
});

const { t } = useI18n();
const now = ref(Date.now());
let timerId = null;

const productItems = computed(() => props.products?.data ?? []);
const pageLinks = computed(() => props.products?.meta?.links ?? []);
const productCount = computed(() => props.flashsale?.products_count ?? productItems.value.length);
const hasActiveSale = computed(() => Boolean(props.flashsale?.end_time));
const pageTitle = computed(() => (hasActiveSale.value ? props.flashsale.name : t("flash_sale.page.title")));
const pageDescription = computed(() =>
    hasActiveSale.value ? props.flashsale.description || t("flash_sale.page.description") : t("flash_sale.page.empty_description"),
);

const remainingTime = computed(() => {
    const remaining = Math.max(0, new Date(props.flashsale?.end_time || now.value).getTime() - now.value);

    return {
        days: String(Math.floor(remaining / 86_400_000)).padStart(2, "0"),
        hours: String(Math.floor((remaining % 86_400_000) / 3_600_000)).padStart(2, "0"),
        minutes: String(Math.floor((remaining % 3_600_000) / 60_000)).padStart(2, "0"),
        seconds: String(Math.floor((remaining % 60_000) / 1_000)).padStart(2, "0"),
    };
});

const elapsedPercentage = computed(() => {
    if (!props.flashsale?.start_time || !props.flashsale?.end_time) return 0;

    const start = new Date(props.flashsale.start_time).getTime();
    const end = new Date(props.flashsale.end_time).getTime();
    if (end <= start) return 0;

    return Math.min(100, Math.max(0, ((now.value - start) / (end - start)) * 100));
});

const tick = () => {
    now.value = Date.now();
};

onMounted(() => {
    tick();
    timerId = window.setInterval(tick, 1_000);
});

onUnmounted(() => {
    if (timerId) window.clearInterval(timerId);
});
</script>

<template>
    <Head :title="pageTitle" />

    <TemplateWrapper :title="pageTitle" :description="pageDescription">
        <main class="min-h-screen bg-background">
            <section class="border-border border-b">
                <div class="container mx-auto px-4 py-4 md:py-5">
                    <nav class="text-muted-foreground flex items-center gap-2 text-sm" :aria-label="t('flash_sale.page.breadcrumb_label')">
                        <Link :href="route('frontend.home')" class="hover:text-foreground focus-visible:ring-primary rounded-sm transition-colors focus-visible:ring-2 focus-visible:outline-none">
                            {{ t("labels.breadcrumb.home") }}
                        </Link>
                        <span aria-hidden="true">/</span>
                        <span class="text-foreground font-semibold">{{ t("flash_sale.page.breadcrumb_current") }}</span>
                    </nav>
                </div>
            </section>

            <section v-if="hasActiveSale" class="relative overflow-hidden bg-foreground text-background">
                <div class="flash-sale-orbit flash-sale-orbit-one" aria-hidden="true"></div>
                <div class="flash-sale-orbit flash-sale-orbit-two" aria-hidden="true"></div>
                <div class="flash-sale-grain" aria-hidden="true"></div>

                <div class="container relative mx-auto px-4 py-10 md:py-16 lg:py-20">
                    <div class="grid items-end gap-10 lg:grid-cols-[minmax(0,1.05fr)_minmax(20rem,0.95fr)] lg:gap-16">
                        <div class="sale-reveal">
                            <div class="mb-5 flex items-center gap-2 text-sm font-bold tracking-[0.08em] text-background/75">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-background text-foreground">
                                    <Flame class="h-4 w-4" aria-hidden="true" />
                                </span>
                                {{ t("flash_sale.page.live_label") }}
                            </div>

                            <h1 class="max-w-3xl text-4xl font-extrabold leading-[0.94] tracking-[-0.055em] text-balance sm:text-5xl md:text-6xl">
                                {{ flashsale.name }}
                            </h1>
                            <p class="mt-6 max-w-2xl text-base leading-7 text-background/74 md:text-lg">
                                {{ flashsale.description || t("flash_sale.page.description") }}
                            </p>

                            <div class="mt-9 flex flex-wrap items-center gap-x-7 gap-y-3 text-sm text-background/75">
                                <span class="flex items-center gap-2">
                                    <PackageOpen class="h-4 w-4" aria-hidden="true" />
                                    {{ t("flash_sale.page.products_available", { count: productCount }) }}
                                </span>
                                <a href="#flashsale-products" class="group flex items-center gap-2 font-semibold text-background transition-opacity hover:opacity-75 focus-visible:ring-background rounded-sm focus-visible:ring-2 focus-visible:outline-none">
                                    {{ t("flash_sale.page.browse_products") }}
                                    <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true" />
                                </a>
                            </div>
                        </div>

                        <aside class="sale-reveal sale-reveal-delayed border-background/25 relative overflow-hidden border-t pt-6 lg:border-l lg:border-t-0 lg:pt-0 lg:pl-10">
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-sm font-semibold text-background/70">{{ t("flash_sale.page.ends_in") }}</p>
                                <Clock3 class="h-5 w-5 text-background/60" aria-hidden="true" />
                            </div>

                            <div class="mt-5 grid grid-cols-4 gap-2 sm:gap-3" aria-live="polite">
                                <div v-for="(value, key) in remainingTime" :key="key" class="border-background/20 bg-background/10 rounded-lg border px-2 py-3 text-center backdrop-blur-sm sm:px-3 sm:py-4">
                                    <strong class="block text-2xl font-extrabold tracking-[-0.06em] sm:text-3xl">{{ value }}</strong>
                                    <span class="mt-1 block text-[10px] font-bold tracking-[0.08em] text-background/60 uppercase">
                                        {{ t("flash_sale.page.time." + key) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-8">
                                <div class="flex justify-between gap-4 text-xs font-semibold text-background/60">
                                    <span>{{ t("flash_sale.page.started") }}</span>
                                    <span>{{ t("flash_sale.page.ending") }}</span>
                                </div>
                                <div class="bg-background/20 mt-3 h-1 overflow-hidden rounded-full">
                                    <div class="bg-background flash-sale-progress h-full rounded-full" :style="{ width: elapsedPercentage + '%' }"></div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>

            <section id="flashsale-products" class="scroll-mt-20 py-10 md:py-14">
                <div class="container mx-auto px-4">
                    <div v-if="hasActiveSale" class="mb-8 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                        <div>
                            <p class="text-primary text-sm font-bold">{{ t("flash_sale.page.collection_label") }}</p>
                            <h2 class="text-foreground mt-2 text-3xl font-extrabold tracking-[-0.045em]">{{ t("flash_sale.page.collection_title") }}</h2>
                        </div>
                        <p class="text-muted-foreground max-w-md text-sm leading-6 sm:text-right">
                            {{ t("flash_sale.page.collection_description") }}
                        </p>
                    </div>

                    <div v-if="productItems.length" class="grid grid-cols-2 gap-x-3 gap-y-7 sm:gap-x-5 md:grid-cols-3 lg:grid-cols-4">
                        <div
                            v-for="(item, index) in productItems"
                            :key="item.id"
                            class="sale-product-reveal"
                            :style="{ '--reveal-delay': index * 55 + 'ms' }"
                        >
                            <ProductCard :product="item.product" />
                        </div>
                    </div>

                    <div v-else class="border-border bg-secondary/45 mx-auto max-w-2xl border px-6 py-14 text-center md:px-12">
                        <div class="bg-background text-primary mx-auto flex h-14 w-14 items-center justify-center rounded-full shadow-[0_12px_30px_-20px_hsl(var(--foreground)/0.65)]">
                            <Sparkles class="h-6 w-6" aria-hidden="true" />
                        </div>
                        <h2 class="text-foreground mt-6 text-2xl font-extrabold tracking-[-0.035em]">
                            {{ hasActiveSale ? t("flash_sale.page.products_empty_title") : t("flash_sale.page.empty_title") }}
                        </h2>
                        <p class="text-muted-foreground mx-auto mt-3 max-w-lg leading-7">
                            {{ hasActiveSale ? t("flash_sale.page.products_empty_description") : t("flash_sale.page.empty_description") }}
                        </p>
                        <Link
                            :href="route('frontend.products')"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-primary mt-7 inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <ShoppingBag class="h-4 w-4" aria-hidden="true" />
                            {{ t("flash_sale.page.explore_products") }}
                        </Link>
                    </div>

                    <nav v-if="pageLinks.length > 3" class="mt-12 flex flex-wrap justify-center gap-2" :aria-label="t('flash_sale.page.pagination_label')">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in pageLinks"
                            :key="link.label"
                            :href="link.url || undefined"
                            preserve-scroll
                            class="border-border text-muted-foreground inline-flex min-h-10 min-w-10 items-center justify-center rounded-full border px-3 text-sm font-semibold transition-colors"
                            :class="[
                                link.active ? 'bg-primary border-primary text-primary-foreground' : 'hover:border-primary hover:text-foreground',
                                !link.url ? 'cursor-not-allowed opacity-35' : 'focus-visible:ring-primary focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </section>
        </main>
    </TemplateWrapper>
</template>

<style scoped>
.flash-sale-orbit {
    position: absolute;
    border: 1px solid color-mix(in oklab, currentColor 19%, transparent);
    border-radius: 999px;
    opacity: 0.48;
    pointer-events: none;
}

.flash-sale-orbit-one {
    top: -20rem;
    right: -11rem;
    width: 42rem;
    height: 42rem;
    animation: orbit-drift 18s cubic-bezier(0.22, 1, 0.36, 1) infinite alternate;
}

.flash-sale-orbit-two {
    bottom: -20rem;
    left: 12%;
    width: 31rem;
    height: 31rem;
    animation: orbit-drift 22s cubic-bezier(0.22, 1, 0.36, 1) -8s infinite alternate-reverse;
}

.flash-sale-grain {
    position: absolute;
    inset: 0;
    opacity: 0.1;
    pointer-events: none;
    background-image: radial-gradient(currentColor 0.65px, transparent 0.75px);
    background-position: 0 0;
    background-size: 5px 5px;
    mask-image: linear-gradient(to bottom, black, transparent 90%);
}

.sale-reveal,
.sale-product-reveal {
    animation: sale-reveal 700ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.sale-reveal-delayed {
    animation-delay: 110ms;
}

.sale-product-reveal {
    animation-delay: var(--reveal-delay);
}

@keyframes orbit-drift {
    from {
        transform: translate3d(-1.5%, -1%, 0) scale(0.98);
    }
    to {
        transform: translate3d(2%, 2%, 0) scale(1.04);
    }
}

@keyframes sale-reveal {
    from {
        opacity: 0;
        transform: translate3d(0, 18px, 0);
        filter: blur(5px);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
        filter: blur(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .flash-sale-orbit,
    .flash-sale-progress,
    .sale-reveal,
    .sale-product-reveal {
        animation: none;
        transition: none;
    }
}
</style>
