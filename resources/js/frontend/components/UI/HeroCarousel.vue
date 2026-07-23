<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed } from "vue";
import emblaCarouselVue from "embla-carousel-vue";
import Autoplay from "embla-carousel-autoplay";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

const props = defineProps({
    template: { type: Object, default: null },
    slides: { type: Array, default: () => [] },
});

const { t } = useI18n();
const slides = computed(() => {
    if (props.slides.length > 0) {
        return props.slides;
    }

    return [
        {
            id: "preview-modest-wear",
            eyebrow: t("labels.carousel.preview.modest_wear_eyebrow"),
            title: t("labels.carousel.preview.modest_wear_title"),
            description: t("labels.carousel.preview.modest_wear_description"),
            image_url: "https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=2400&q=85",
            href: route("frontend.products"),
            target_anchor: t("labels.carousel.preview.action"),
        },
        {
            id: "preview-electronics",
            eyebrow: t("labels.carousel.preview.electronics_eyebrow"),
            title: t("labels.carousel.preview.electronics_title"),
            description: t("labels.carousel.preview.electronics_description"),
            image_url: "https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=2400&q=85",
            href: route("frontend.products"),
            target_anchor: t("labels.carousel.preview.action"),
        },
        {
            id: "preview-essentials",
            eyebrow: t("labels.carousel.preview.essentials_eyebrow"),
            title: t("labels.carousel.preview.essentials_title"),
            description: t("labels.carousel.preview.essentials_description"),
            image_url: "https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=2400&q=85",
            href: route("frontend.products"),
            target_anchor: t("labels.carousel.preview.action"),
        },
    ];
});
const hasSlides = computed(() => slides.value.length > 0);
const [emblaRef, emblaApi] = emblaCarouselVue({ loop: true }, [Autoplay({ delay: 6000, stopOnInteraction: true })]);

const scrollPrev = () => emblaApi.value?.scrollPrev();
const scrollNext = () => emblaApi.value?.scrollNext();
</script>

<template>
    <section v-if="hasSlides" class="bg-muted relative overflow-hidden">
        <div class="embla" ref="emblaRef">
            <div class="embla__container flex">
                <div
                    v-for="(slide, index) in slides"
                    :key="slide.id || slide.slug || index"
                    class="embla__slide relative aspect-[4/3] min-w-0 flex-[0_0_100%] sm:aspect-[16/8] lg:aspect-[3/1]"
                >
                    <img :src="slide.image_url" class="absolute inset-0 h-full w-full object-cover" :alt="slide.title || slide.name || ''" />
                    <div class="absolute inset-0 flex items-end bg-black/35 px-4 py-6 sm:px-8 sm:py-10 lg:px-16 lg:py-14">
                        <div class="max-w-2xl">
                            <p v-if="slide.eyebrow" class="mb-3 text-xs font-semibold tracking-[0.14em] text-white/80 uppercase">
                                {{ slide.eyebrow }}
                            </p>
                            <h2 v-if="slide.title || slide.name" class="font-display text-3xl leading-tight text-white md:text-5xl lg:text-6xl">
                                {{ slide.title || slide.name }}
                            </h2>
                            <p v-if="slide.description" class="mt-3 max-w-xl text-sm leading-relaxed text-white/85 md:text-base">
                                {{ slide.description }}
                            </p>
                            <div v-if="slide.target_link || slide.href" class="mt-5">
                                <Link
                                    :href="slide.target_link || slide.href"
                                    class="text-foreground inline-flex min-h-11 items-center border border-white bg-white px-5 py-3 text-sm font-semibold transition-colors duration-200 hover:bg-transparent hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black focus-visible:outline-none motion-reduce:transition-none"
                                >
                                    {{ slide.target_anchor }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Button
            @click="scrollPrev"
            :icon="ChevronLeft"
            size="icon"
            :aria-label="t('labels.carousel.previous')"
            class="hover:text-foreground absolute top-1/2 left-4 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-md border border-white/30 bg-black/25 text-white transition-colors duration-200 hover:bg-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none motion-reduce:transition-none md:left-6"
        />
        <Button
            @click="scrollNext"
            :icon="ChevronRight"
            size="icon"
            :aria-label="t('labels.carousel.next')"
            class="hover:text-foreground absolute top-1/2 right-4 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-md border border-white/30 bg-black/25 text-white transition-colors duration-200 hover:bg-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none motion-reduce:transition-none md:right-6"
        />
    </section>
</template>
