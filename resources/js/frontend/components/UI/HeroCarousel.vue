<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed, ref, watch } from "vue";
import emblaCarouselVue from "embla-carousel-vue";
import Autoplay from "embla-carousel-autoplay";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

const props = defineProps({
    template: { type: Object, default: null },
    slides: { type: [Array, Object], default: () => [] },
});

const { t } = useI18n();
const failedSlides = ref(new Set());
const slides = computed(() => (Array.isArray(props.slides) ? props.slides : props.slides?.data || []).filter((slide) => slide?.image_url));
const hasSlides = computed(() => slides.value.length > 0);
const slideKey = (slide, index) => slide.id || slide.slug || index;
const hasSlideImage = (slide, index) => !failedSlides.value.has(slideKey(slide, index));
const handleSlideError = (slide, index) => {
    failedSlides.value = new Set([...failedSlides.value, slideKey(slide, index)]);
};
const [emblaRef, emblaApi] = emblaCarouselVue({ loop: true }, [Autoplay({ delay: 6000, stopOnInteraction: true })]);
const selectedIndex = ref(0);
const updateSelectedIndex = () => {
    selectedIndex.value = emblaApi.value?.selectedScrollSnap() || 0;
};

watch(emblaApi, (api) => {
    if (!api) return;

    api.on("select", updateSelectedIndex);
    api.on("reInit", updateSelectedIndex);
    updateSelectedIndex();
}, { flush: "post" });

const scrollPrev = () => emblaApi.value?.scrollPrev();
const scrollNext = () => emblaApi.value?.scrollNext();
const scrollTo = (index) => emblaApi.value?.scrollTo(index);
</script>

<template>
    <section v-if="hasSlides" class="bg-background py-3 md:py-5" :aria-label="t('labels.home.promotions_label')">
        <div class="container mx-auto px-4 md:px-8">
            <div class="relative">
                <div ref="emblaRef" class="embla overflow-hidden rounded-2xl">
                    <div class="embla__container flex">
                        <div v-for="(slide, index) in slides" :key="slide.id || slide.slug || index" class="embla__slide relative min-w-0 overflow-hidden rounded-2xl bg-secondary aspect-[1.8/1] sm:aspect-[3.3/1] lg:aspect-[2.7/1]">
                            <img v-if="hasSlideImage(slide, index)" :src="slide.image_url" :alt="slide.title || slide.name || ''" class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" @error="handleSlideError(slide, index)" />
                            <div class="absolute inset-0 flex items-end px-5 py-5 sm:px-8 sm:py-7 lg:px-9 lg:py-8" :class="hasSlideImage(slide, index) ? 'bg-gradient-to-r from-black/70 via-black/25 to-transparent text-white' : 'bg-secondary text-foreground'">
                                <div class="max-w-xl">
                                    <p v-if="slide.eyebrow" class="mb-2 text-[10px] font-bold tracking-[0.14em] uppercase" :class="hasSlideImage(slide, index) ? 'text-white/75' : 'text-primary'">{{ slide.eyebrow }}</p>
                                    <h2 v-if="slide.title || slide.name" class="text-xl leading-tight font-bold tracking-[-0.03em] sm:text-2xl">{{ slide.title || slide.name }}</h2>
                                    <p v-if="slide.description" class="mt-2 max-w-lg text-[11px] leading-5 sm:text-xs" :class="hasSlideImage(slide, index) ? 'text-white/80' : 'text-muted-foreground'">{{ slide.description }}</p>
                                    <Link v-if="slide.target_link || slide.href" :href="slide.target_link || slide.href" class="mt-3 inline-flex min-h-9 items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-bold text-foreground transition-colors hover:bg-secondary focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none">
                                        {{ slide.target_anchor || t("labels.carousel.preview.action") }}
                                        <ChevronRight class="h-4 w-4" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Button @click="scrollPrev" :icon="ChevronLeft" size="icon" :aria-label="t('labels.carousel.previous')" class="absolute top-1/2 left-3 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-foreground shadow-md hover:bg-white focus-visible:ring-2 focus-visible:ring-white sm:left-5" />
                <Button @click="scrollNext" :icon="ChevronRight" size="icon" :aria-label="t('labels.carousel.next')" class="absolute top-1/2 right-3 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-foreground shadow-md hover:bg-white focus-visible:ring-2 focus-visible:ring-white sm:right-5" />
            </div>
            <div v-if="slides.length > 1" class="mt-3 flex items-center justify-center gap-2" role="tablist" :aria-label="t('labels.carousel.navigation')">
                <button
                    v-for="(_, index) in slides"
                    :key="index"
                    type="button"
                    role="tab"
                    :aria-selected="selectedIndex === index"
                    :aria-label="t('labels.carousel.go_to_slide', { index: index + 1 })"
                    class="h-2 rounded-full transition-[width,background-color] focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none motion-reduce:transition-none"
                    :class="selectedIndex === index ? 'bg-primary w-6' : 'bg-border w-2 hover:bg-primary/60'"
                    @click="scrollTo(index)"
                ></button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.embla__slide {
    flex: 0 0 100%;
}

@media (min-width: 640px) {
    .embla__container {
        gap: 1rem;
    }

    .embla__slide {
        flex-basis: calc((100% - 1rem) / 2);
    }
}

@media (min-width: 1024px) {
    .embla__slide {
        flex-basis: calc((100% - 2rem) / 3);
    }
}
</style>
