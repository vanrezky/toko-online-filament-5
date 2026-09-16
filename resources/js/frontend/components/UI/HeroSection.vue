<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { ArrowRight, Headphones, ShieldCheck, Sparkles, Truck } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";

const props = defineProps({
    template: { type: Object, default: null },
});

const { t } = useI18n();
const title = computed(() => getSectionContent(props.template, "hero", "title", t("labels.hero.default_title")));
const subtitle = computed(() => getSectionContent(props.template, "hero", "subtitle", t("labels.hero.default_subtitle")));
const imageUrl = computed(() => getSectionContent(props.template, "hero", "image_url", "/images/hero/bristol-products.webp"));
const eyebrow = computed(() => getSectionContent(props.template, "hero", "eyebrow", t("labels.home.hero_eyebrow")));
const badge = computed(() => getSectionContent(props.template, "hero", "badge", ""));
const primaryLabel = computed(() => getSectionContent(props.template, "hero", "button_text", t("labels.actions.start_shopping")));
const primaryLink = computed(() => getSectionContent(props.template, "hero", "button_link", route("frontend.products")));
const secondaryLabel = computed(() => getSectionContent(props.template, "hero", "secondary_text", t("labels.home.hero_secondary_action")));
const secondaryLink = computed(() => getSectionContent(props.template, "hero", "secondary_link", route("frontend.products")));
const promoLabel = computed(() => getSectionContent(props.template, "hero", "promo_label", t("labels.home.hero_promo_label")));
const promoValue = computed(() => getSectionContent(props.template, "hero", "promo_value", t("labels.home.hero_promo_value")));
const trustPoints = computed(() => {
    const configured = getSectionContent(props.template, "hero", "trust_points", null);
    let parsed = configured;

    if (typeof configured === "string") {
        try {
            parsed = JSON.parse(configured);
        } catch {
            parsed = null;
        }
    }

    if (Array.isArray(parsed) && parsed.length > 0) {
        return parsed.slice(0, 3).map((point, index) => ({
            ...point,
            icon: [Truck, ShieldCheck, Headphones][index] || Sparkles,
        }));
    }

    return [
        { title: t("labels.home.trust.shipping_title"), description: t("labels.home.trust.shipping_description"), icon: Truck },
        { title: t("labels.home.trust.original_title"), description: t("labels.home.trust.original_description"), icon: ShieldCheck },
        { title: t("labels.home.trust.support_title"), description: t("labels.home.trust.support_description"), icon: Headphones },
    ];
});
</script>

<template>
    <section class="bg-background py-4 md:py-5">
        <div class="container mx-auto px-4 md:px-8">
            <div class="hero-layout overflow-hidden rounded-2xl">
                <div class="hero-copy relative z-10 flex flex-col justify-center px-6 py-8 lg:py-10">
                    <p class="text-primary mb-3 text-xs font-semibold uppercase">{{ eyebrow }}</p>
                    <h1 class="hero-title font-bold text-balance">{{ title }}</h1>
                    <p class="hero-description mt-4 max-w-sm text-sm leading-5">{{ subtitle }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="primaryLink"
                            class="bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-primary inline-flex min-h-11 items-center justify-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transition-none lg:px-5"
                        >
                            {{ primaryLabel }}
                            <ArrowRight class="h-4 w-4 shrink-0" aria-hidden="true" />
                        </Link>
                        <Link
                            :href="secondaryLink"
                            class="border-primary text-primary bg-background/80 hover:bg-primary/10 focus-visible:ring-primary inline-flex min-h-11 items-center justify-center rounded-lg border px-4 py-3 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transition-none lg:px-5"
                        >
                            {{ secondaryLabel }}
                        </Link>
                    </div>
                </div>

                <div class="hero-visual relative min-w-0">
                    <img :src="imageUrl" :alt="title" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high" decoding="async" />
                    <div class="hero-image-edge absolute inset-0" aria-hidden="true"></div>
                    <div
                        v-if="badge || promoValue"
                        class="hero-promo bg-primary text-primary-foreground absolute top-5 right-5 flex h-24 w-24 flex-col items-center justify-center rounded-full p-3 text-center sm:top-8 sm:right-8 sm:h-28 sm:w-28"
                    >
                        <span class="max-w-full text-xs leading-tight break-words">{{ badge || promoLabel }}</span>
                        <strong class="mt-1 max-w-full text-3xl leading-none break-words">{{ promoValue }}</strong>
                    </div>
                </div>

                <aside class="hero-services flex items-center px-6 py-8 lg:px-7" :aria-label="t('labels.home.trust.services_label')">
                    <div class="w-full space-y-6">
                        <div v-for="point in trustPoints" :key="point.title" class="flex items-center gap-4">
                            <span class="text-primary bg-primary/10 flex h-14 w-14 shrink-0 items-center justify-center rounded-full">
                                <component :is="point.icon" class="h-6 w-6" :stroke-width="1.5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold">{{ point.title }}</h2>
                                <p class="hero-description mt-1 text-xs leading-5">{{ point.description }}</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-layout {
    --hero-surface: color-mix(in srgb, hsl(var(--primary)) 5%, hsl(var(--background)));
    display: grid;
    background: var(--hero-surface);
    color: hsl(var(--foreground));
}

.hero-title {
    max-width: 14ch;
    font-size: clamp(2rem, 3.25vw, 2.75rem);
    line-height: 1.05;
    letter-spacing: -0.04em;
    overflow-wrap: anywhere;
}

.hero-description {
    color: hsl(var(--muted-foreground));
    overflow-wrap: anywhere;
}

.hero-visual {
    aspect-ratio: 3 / 2;
}

.hero-image-edge {
    background: linear-gradient(to bottom, var(--hero-surface), transparent 15%, transparent 90%, var(--hero-surface));
}

@media (min-width: 640px) {
    .hero-layout {
        grid-template-columns: 1fr 1fr;
    }

    .hero-visual {
        aspect-ratio: auto;
        min-height: 22rem;
    }

    .hero-image-edge {
        background: linear-gradient(to right, var(--hero-surface), transparent 15%, transparent 90%, var(--hero-surface));
    }

    .hero-services {
        grid-column: 1 / -1;
    }
}

@media (min-width: 1024px) {
    .hero-layout {
        grid-template-columns: minmax(0, 34fr) minmax(0, 40fr) minmax(0, 26fr);
    }

    .hero-copy {
        padding-left: clamp(2rem, 4.5vw, 4.5rem);
        padding-right: 0.5rem;
    }

    .hero-services {
        grid-column: auto;
    }
}
</style>
