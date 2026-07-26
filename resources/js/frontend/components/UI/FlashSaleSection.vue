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

const timeLeft = ref({
    hours: "00",
    minutes: "00",
    seconds: "00",
});

let intervalId = null;

const calculateTimeLeft = () => {
    if (!props.flashsales?.end_time) return;

    const end = new Date(props.flashsales.end_time).getTime();
    const now = new Date().getTime();
    const diff = end - now;

    if (diff > 0) {
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        timeLeft.value = {
            hours: String(hours).padStart(2, "0"),
            minutes: String(minutes).padStart(2, "0"),
            seconds: String(seconds).padStart(2, "0"),
        };
    }
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
    return props.flashsales.products.map((item) => item.product);
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
        scrollContainer.value.addEventListener("scroll", checkScroll);
        checkScroll();
    }
});

onUnmounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.removeEventListener("scroll", checkScroll);
    }
});
</script>

<template>
    <section class="from-destructive/5 via-destructive/10 to-destructive/5 relative overflow-hidden bg-gradient-to-r py-8 md:py-12">
        <!-- Decorative Elements -->
        <div class="bg-destructive/10 absolute -top-20 -right-20 h-64 w-64 rounded-full blur-3xl"></div>
        <div class="bg-destructive/10 absolute -bottom-20 -left-20 h-64 w-64 rounded-full blur-3xl"></div>

        <div class="container mx-auto px-4">
            <!-- Header -->
            <div class="mb-6">
                <!-- Desktop Header -->
                <div class="hidden items-center justify-between md:flex">
                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <span class="from-destructive inline-flex h-[2px] w-8 items-center justify-center bg-gradient-to-r to-rose-500"></span>
                            <span class="text-destructive flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase">
                                <Zap class="h-3 w-3" /> Promo Terbatas
                            </span>
                        </div>
                        <h2 class="text-foreground mt-1 text-xl font-bold md:text-2xl">{{ sectionTitle }}</h2>
                        <p class="text-muted-foreground mt-1 text-sm">{{ subtitle }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Countdown Timer -->
                        <div class="text-foreground flex items-center gap-2">
                            <Clock class="text-destructive h-5 w-5" />
                            <span class="text-sm font-semibold">Berakhir dalam:</span>
                            <div class="flex items-center gap-1 font-bold">
                                <span
                                    class="from-destructive shadow-destructive/30 min-w-[36px] rounded-lg bg-gradient-to-br to-rose-600 px-2 py-1.5 text-center text-sm text-white shadow-lg"
                                    >{{ timeLeft.hours }}</span
                                >
                                <span class="text-destructive font-bold">:</span>
                                <span
                                    class="from-destructive shadow-destructive/30 min-w-[36px] rounded-lg bg-gradient-to-br to-rose-600 px-2 py-1.5 text-center text-sm text-white shadow-lg"
                                    >{{ timeLeft.minutes }}</span
                                >
                                <span class="text-destructive font-bold">:</span>
                                <span
                                    class="from-destructive shadow-destructive/30 min-w-[36px] rounded-lg bg-gradient-to-br to-rose-600 px-2 py-1.5 text-center text-sm text-white shadow-lg"
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
                                class="text-foreground flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-md transition-all duration-300 hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Scroll left"
                            />
                            <Button
                                @click="scroll('right')"
                                :disabled="!canScrollRight"
                                :icon="ChevronRight"
                                size="icon"
                                class="from-destructive shadow-destructive/30 hover:shadow-destructive/40 flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br to-rose-600 text-white shadow-lg transition-all duration-300 hover:shadow-xl active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Scroll right"
                            />
                        </div>
                    </div>
                </div>

                <!-- Mobile Header -->
                <div class="flex items-start justify-between gap-4 md:hidden">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="from-destructive inline-flex h-[2px] w-6 items-center justify-center bg-gradient-to-r to-rose-500"></span>
                            <span class="text-destructive flex items-center gap-1 text-xs font-bold tracking-widest uppercase">
                                <Zap class="h-3 w-3" /> Promo
                            </span>
                        </div>
                        <h2 class="text-foreground mt-1 text-xl font-bold">{{ flashsales.name }}</h2>
                    </div>

                    <!-- Mobile Timer & Scroll Buttons -->
                    <div class="flex flex-col items-end gap-2">
                        <div class="flex items-center gap-2">
                            <Clock class="text-destructive h-4 w-4" />
                            <div class="flex items-center gap-1 text-xs font-bold">
                                <span class="from-destructive rounded bg-gradient-to-br to-rose-600 px-2 py-0.5 text-white shadow">{{
                                    timeLeft.hours
                                }}</span>
                                <span class="text-destructive font-bold">:</span>
                                <span class="from-destructive rounded bg-gradient-to-br to-rose-600 px-2 py-0.5 text-white shadow">{{
                                    timeLeft.minutes
                                }}</span>
                                <span class="text-destructive font-bold">:</span>
                                <span class="from-destructive rounded bg-gradient-to-br to-rose-600 px-2 py-0.5 text-white shadow">{{
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
                                class="text-foreground flex h-9 w-9 items-center justify-center rounded-xl bg-white shadow transition-all disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Scroll left"
                            />
                            <Button
                                @click="scroll('right')"
                                :disabled="!canScrollRight"
                                :icon="ChevronRight"
                                size="icon"
                                class="from-destructive flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br to-rose-600 text-white shadow-lg transition-all active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Scroll right"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scrollable Container with Fade Edges -->
            <div class="relative">
                <!-- Left Fade -->
                <div
                    class="from-destructive/10 via-destructive/5 pointer-events-none absolute top-0 left-0 z-10 h-full w-8 to-transparent transition-opacity duration-300 md:w-12"
                    :class="canScrollLeft ? 'opacity-100' : 'opacity-0'"
                ></div>

                <!-- Right Fade -->
                <div
                    class="from-destructive/10 via-destructive/5 pointer-events-none absolute top-0 right-0 z-10 h-full w-8 to-transparent transition-opacity duration-300 md:w-12"
                    :class="canScrollRight ? 'opacity-100' : 'opacity-0'"
                ></div>

                <div
                    ref="scrollContainer"
                    class="scrollbar-hidden -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 lg:grid lg:snap-none lg:grid-cols-5 lg:gap-4 lg:overflow-visible"
                >
                    <div v-for="product in flashSaleProducts" :key="product.uuid || product.id" class="w-44 shrink-0 snap-start md:w-64 lg:shrink-0">
                        <ProductCard :product="product" />
                    </div>

                    <!-- View All Card (Hidden on Desktop) -->
                    <div class="w-44 shrink-0 snap-start md:w-52 lg:hidden">
                        <Link
                            :href="route('frontend.flashsales')"
                            class="group border-destructive/30 from-destructive/5 hover:border-destructive relative flex h-full min-h-[320px] flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed bg-gradient-to-br to-white transition-all duration-300 hover:shadow-lg md:min-h-[360px]"
                        >
                            <!-- Decorative -->
                            <div
                                class="bg-destructive/10 absolute -top-4 -right-4 h-20 w-20 rounded-full transition-transform duration-500 group-hover:scale-150"
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
                    class="from-destructive shadow-destructive/30 hover:shadow-destructive/40 inline-flex items-center gap-2 rounded-full bg-gradient-to-r to-rose-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl"
                >
                    {{ t("flash_sale.page.browse_all") }}
                    <ChevronRight class="h-4 w-4" />
                </Link>
            </div>
        </div>
    </section>
</template>
