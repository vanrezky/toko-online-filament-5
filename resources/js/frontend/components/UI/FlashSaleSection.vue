<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, computed, onMounted, onUnmounted } from "vue";
import ProductCard from "./ProductCard.vue";
import { Link } from "@inertiajs/vue3";
import { Clock, ChevronRight, ChevronLeft, Zap } from "lucide-vue-next";
import { getSectionContent } from "../../lib/utils";
import { useI18n } from "vue-i18n";

const props = defineProps({
    flashsales: {
        type: Object,
        required: true,
    },
    template: { type: Object, default: null },
});
const { t } = useI18n();

const sectionTitle = computed(() => getSectionContent(props.template, "flash_sale", "title", props.flashsales?.name || "Flash Sale"));
const subtitle = computed(() => getSectionContent(props.template, "flash_sale", "subtitle", "Dapatkan harga spesial dengan periode terbatas"));
const showTimer = computed(() => {
    const value = getSectionContent(props.template, "flash_sale", "show_timer", "1");

    return ![false, 0, "0", "false"].includes(value);
});
const limit = computed(() => Math.max(1, Number(getSectionContent(props.template, "flash_sale", "limit", 8)) || 8));

const timeLeft = ref({
    hours: "00",
    minutes: "00",
    seconds: "00",
});

let intervalId = null;
let scrollFrameId = null;

const calculateTimeLeft = () => {
    if (!props.flashsales?.end_time) return;

    const end = new Date(props.flashsales.end_time).getTime();
    const now = new Date().getTime();
    const diff = end - now;

    if (diff <= 0) {
        timeLeft.value = { hours: "00", minutes: "00", seconds: "00" };
        if (intervalId) clearInterval(intervalId);
        return;
    }

    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    timeLeft.value = {
        hours: String(hours).padStart(2, "0"),
        minutes: String(minutes).padStart(2, "0"),
        seconds: String(seconds).padStart(2, "0"),
    };
};

onMounted(() => {
    calculateTimeLeft();
    intervalId = setInterval(calculateTimeLeft, 1000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});

const flashSaleProducts = computed(() => {
    if (!props.flashsales?.products) return [];
    return props.flashsales.products.map((item) => item.product).filter(Boolean).slice(0, limit.value);
});

const scrollContainer = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(true);

const checkScroll = () => {
    if (scrollContainer.value) {
        const { scrollLeft, scrollWidth, clientWidth } = scrollContainer.value;
        canScrollLeft.value = scrollLeft > 0;
        canScrollRight.value = scrollLeft < scrollWidth - clientWidth - 10;
    }
};

const scheduleScrollCheck = () => {
    if (scrollFrameId) return;
    scrollFrameId = requestAnimationFrame(() => {
        checkScroll();
        scrollFrameId = null;
    });
};

const scroll = (direction) => {
    if (scrollContainer.value) {
        const scrollAmount = 280;
        scrollContainer.value.scrollBy({
            left: direction === "left" ? -scrollAmount : scrollAmount,
            behavior: "smooth",
        });
    }
};

onMounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.addEventListener("scroll", scheduleScrollCheck, { passive: true });
        checkScroll();
    }
});

onUnmounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.removeEventListener("scroll", scheduleScrollCheck);
    }
    if (scrollFrameId) cancelAnimationFrame(scrollFrameId);
});
</script>

<template>
    <section class="border-destructive/15 from-secondary via-background to-background relative overflow-hidden border-y bg-gradient-to-br py-8 md:py-12">
        <div class="container relative mx-auto px-4">
            <!-- Header -->
            <div class="mb-6 md:mb-8">
                <!-- Desktop Header -->
                <div class="hidden items-center justify-between md:flex">
                    <div class="relative">
                        <div class="text-destructive flex items-center gap-1.5 text-xs font-bold">
                                <Zap class="h-3 w-3" /> Promo Terbatas
                        </div>
                        <h2 class="text-foreground mt-1 text-xl font-bold md:text-2xl">{{ sectionTitle }}</h2>
                        <p class="text-muted-foreground mt-1 max-w-xl text-sm">{{ subtitle }}</p>
                    </div>

                    <div class="flex items-center gap-5">
                        <!-- Countdown Timer -->
                        <div v-if="showTimer" class="text-foreground flex items-center gap-2.5" role="timer" aria-live="off" aria-label="Berakhir dalam waktu {{ timeLeft.hours }} jam {{ timeLeft.minutes }} menit {{ timeLeft.seconds }} detik">
                            <Clock class="text-destructive h-5 w-5" aria-hidden="true" />
                            <span class="text-sm font-semibold">Berakhir dalam:</span>
                            <div class="flex items-center gap-1.5 font-bold" aria-hidden="true">
                                <span
                                    class="bg-destructive text-destructive-foreground min-w-[38px] rounded-md px-2 py-1.5 text-center text-sm shadow-sm"
                                    >{{ timeLeft.hours }}</span
                                >
                                <span class="text-destructive font-bold">:</span>
                                <span
                                    class="bg-destructive text-destructive-foreground min-w-[38px] rounded-md px-2 py-1.5 text-center text-sm shadow-sm"
                                    >{{ timeLeft.minutes }}</span
                                >
                                <span class="text-destructive font-bold">:</span>
                                <span
                                    class="bg-destructive text-destructive-foreground min-w-[38px] rounded-md px-2 py-1.5 text-center text-sm shadow-sm"
                                    >{{ timeLeft.seconds }}</span
                                >
                            </div>
                        </div>

                        <!-- Scroll Buttons -->
                        <div class="flex items-center gap-2">
                            <Button
                                @click="scroll('left')"
                                :disabled="!canScrollLeft"
                                :icon="ChevronLeft"
                                size="icon"
                                class="border-border bg-background text-foreground hover:bg-secondary focus-visible:ring-primary"
                                aria-label="Geser produk promo ke kiri"
                            />
                            <Button
                                @click="scroll('right')"
                                :disabled="!canScrollRight"
                                :icon="ChevronRight"
                                size="icon"
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90 focus-visible:ring-destructive/30"
                                aria-label="Geser produk promo ke kanan"
                            />
                        </div>
                    </div>
                </div>

                <!-- Mobile Header -->
                <div class="flex items-start justify-between gap-4 md:hidden">
                    <div>
                        <div class="text-destructive flex items-center gap-1 text-xs font-bold">
                                <Zap class="h-3 w-3" /> Promo
                        </div>
                        <h2 class="text-foreground mt-1 text-xl font-bold">{{ sectionTitle }}</h2>
                    </div>

                    <!-- Mobile Timer & Scroll Buttons -->
                    <div class="flex flex-col items-end gap-2">
                        <div v-if="showTimer" class="flex items-center gap-2" role="timer" aria-live="off" aria-label="Berakhir dalam waktu {{ timeLeft.hours }} jam {{ timeLeft.minutes }} menit {{ timeLeft.seconds }} detik">
                            <Clock class="text-destructive h-4 w-4" aria-hidden="true" />
                            <div class="flex items-center gap-1 text-xs font-bold" aria-hidden="true">
                                <span class="bg-destructive text-destructive-foreground rounded px-2 py-0.5 shadow-sm">{{
                                    timeLeft.hours
                                }}</span>
                                <span class="text-destructive font-bold">:</span>
                                <span class="bg-destructive text-destructive-foreground rounded px-2 py-0.5 shadow-sm">{{
                                    timeLeft.minutes
                                }}</span>
                                <span class="text-destructive font-bold">:</span>
                                <span class="bg-destructive text-destructive-foreground rounded px-2 py-0.5 shadow-sm">{{
                                    timeLeft.seconds
                                }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                @click="scroll('left')"
                                :disabled="!canScrollLeft"
                                :icon="ChevronLeft"
                                size="icon"
                                class="h-11 w-11 border-border bg-background text-foreground hover:bg-secondary focus-visible:ring-primary"
                                aria-label="Geser produk promo ke kiri"
                            />
                            <Button
                                @click="scroll('right')"
                                :disabled="!canScrollRight"
                                :icon="ChevronRight"
                                size="icon"
                                class="h-11 w-11 bg-destructive text-destructive-foreground hover:bg-destructive/90 focus-visible:ring-destructive/30"
                                aria-label="Geser produk promo ke kanan"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Container with Fade Edges -->
            <div class="relative">
                <div
                    ref="scrollContainer"
                    class="flash-sale-products-grid scrollbar-hidden -mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-4 sm:gap-4 md:gap-5 lg:grid lg:snap-none lg:grid-cols-6 lg:gap-5 lg:overflow-visible"
                >
                    <div v-for="product in flashSaleProducts" :key="product.uuid || product.id" class="flash-sale-product w-44 shrink-0 snap-start md:w-64 lg:w-auto lg:shrink-0">
                        <ProductCard :product="product" />
                    </div>

                    <!-- View All Card (Hidden on Desktop) -->
                    <div class="w-44 shrink-0 snap-start md:w-52 lg:hidden">
                        <Link
                            :href="route('frontend.flashsales')"
                            class="border-border bg-background hover:border-primary group relative flex h-full min-h-[320px] flex-col items-center justify-center overflow-hidden border border-dashed transition-[border-color,box-shadow,transform] duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:ring-primary focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none md:min-h-[360px] motion-reduce:transform-none motion-reduce:transition-none"
                        >
                            <!-- Decorative -->
                            <div
                                class="bg-destructive/5 absolute -top-4 -right-4 h-20 w-20 rounded-full transition-transform duration-500 group-hover:scale-150 motion-reduce:transition-none"
                            ></div>

                            <div class="mb-3 text-5xl transition-transform duration-300 group-hover:scale-110">🔥</div>
                            <span class="text-foreground mb-1 text-sm font-semibold">Lihat Semua</span>
                            <span class="text-muted-foreground text-xs">{{ flashSaleProducts.length }}+ Promo</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- View All Link (Desktop Only) -->
            <div class="mt-8 text-center lg:block">
                <Link
                    :href="route('frontend.flashsales')"
                    class="bg-secondary text-foreground hover:bg-primary hover:text-primary-foreground group inline-flex min-h-11 items-center gap-2 px-8 py-3 text-sm font-semibold transition-[background-color,color,transform] duration-300 hover:-translate-y-0.5 focus-visible:ring-primary focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transform-none motion-reduce:transition-none"
                >
                    {{ t("flash_sale.page.browse_all") }}
                    <ChevronRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
@media (min-width: 1024px) {
    .flash-sale-products-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 1.25rem;
        overflow: visible;
        scroll-snap-type: none;
    }

    .flash-sale-product {
        width: auto;
    }
}
</style>
