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
const imageUrl = computed(() => getSectionContent(props.template, "hero", "image_url", ""));
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

    if (Array.isArray(configured) && configured.length > 0) {
        return configured.slice(0, 3).map((point, index) => ({
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
    <section class="bg-background py-4 md:py-5 lg:py-6">
        <div class="container mx-auto px-4 md:px-8">
            <div class="hero-layout overflow-hidden rounded-[2rem] bg-secondary shadow-[0_20px_50px_-38px_hsl(var(--foreground)/0.45)]">
                <div class="relative isolate min-h-[31rem] overflow-hidden sm:min-h-[34rem] lg:min-h-[30rem]">
                    <img
                        v-if="imageUrl"
                        :src="imageUrl"
                        :alt="title"
                        class="absolute inset-0 h-full w-full object-cover"
                        fetchpriority="high"
                        decoding="async"
                    />
                    <div v-if="imageUrl" class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/15" aria-hidden="true"></div>
                    <div v-else class="absolute inset-0 bg-secondary" aria-hidden="true">
                        <div class="absolute -top-24 left-1/3 h-72 w-72 rounded-full bg-white/45 blur-3xl"></div>
                        <div class="absolute -right-20 -bottom-32 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
                    </div>

                    <div class="relative z-10 flex h-full min-h-[31rem] flex-col justify-end p-6 sm:min-h-[34rem] sm:p-10 lg:min-h-[30rem] lg:max-w-3xl lg:p-14">
                        <p class="mb-4 text-xs font-bold tracking-[0.14em] text-white/80 uppercase" :class="!imageUrl && 'text-primary'">{{ eyebrow }}</p>
                        <h1 class="max-w-[14ch] text-4xl leading-[0.98] font-bold tracking-[-0.045em] text-balance sm:text-5xl lg:text-[3.7rem]" :class="imageUrl ? 'text-white' : 'text-foreground'">
                            {{ title }}
                        </h1>
                        <p class="mt-5 max-w-md text-sm leading-6 sm:text-base" :class="imageUrl ? 'text-white/80' : 'text-muted-foreground'">{{ subtitle }}</p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <Link
                                :href="primaryLink"
                                class="bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-primary inline-flex min-h-11 items-center gap-2 rounded-xl px-5 py-3 text-sm font-bold shadow-[0_12px_24px_-16px_hsl(var(--primary)/0.9)] transition-colors focus-visible:ring-2 focus-visible:outline-none motion-reduce:transition-none"
                            >
                                {{ primaryLabel }}
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Link>
                            <Link
                                :href="secondaryLink"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border px-5 py-3 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:outline-none motion-reduce:transition-none"
                                :class="imageUrl ? 'border-white text-white hover:bg-white/15 focus-visible:ring-white' : 'border-primary text-primary hover:bg-primary/10 focus-visible:ring-primary'"
                            >
                                {{ secondaryLabel }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="badge || promoValue" class="bg-primary text-primary-foreground absolute top-6 right-6 z-20 flex h-24 w-24 rotate-6 flex-col items-center justify-center rounded-full text-center shadow-[0_16px_28px_-18px_hsl(var(--primary)/0.95)] sm:top-10 sm:right-10 sm:h-28 sm:w-28">
                        <span v-if="badge" class="text-[10px] font-semibold leading-tight">{{ badge }}</span>
                        <span v-else class="text-[10px] font-semibold leading-tight">{{ promoLabel }}</span>
                        <strong class="text-2xl leading-none">{{ promoValue }}</strong>
                    </div>
                </div>

                <aside class="bg-secondary/80 flex items-center p-6 sm:p-10 lg:p-9" :aria-label="t('labels.home.trust.services_label')">
                    <div class="w-full">
                        <p class="text-primary mb-6 text-xs font-bold tracking-[0.14em] uppercase">{{ t("labels.home.trust.services_label") }}</p>
                        <div class="space-y-6">
                            <div v-for="point in trustPoints" :key="point.title" class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/75 text-primary">
                                    <component :is="point.icon" class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 class="text-foreground text-sm font-bold">{{ point.title }}</h2>
                                    <p class="text-muted-foreground mt-1 text-xs leading-5">{{ point.description }}</p>
                                </div>
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
    display: grid;
}

@media (min-width: 1024px) {
    .hero-layout {
        grid-template-columns: minmax(0, 7fr) minmax(18rem, 3fr);
    }
}
</style>
