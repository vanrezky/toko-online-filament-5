<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { ChevronRight } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";

const props = defineProps({
    template: { type: Object, default: null },
});

const { t } = useI18n();
const title = computed(() => getSectionContent(props.template, "hero", "title", t("labels.hero.default_title")));
const subtitle = computed(() => getSectionContent(props.template, "hero", "subtitle", t("labels.hero.default_subtitle")));
const imageUrl = computed(() => getSectionContent(props.template, "hero", "image_url", ""));
const badge = computed(() => getSectionContent(props.template, "hero", "badge", ""));
const buttonText = computed(() => getSectionContent(props.template, "hero", "button_text", "Belanja Sekarang"));
const hasBackgroundImage = computed(() => imageUrl.value.length > 0);
const normalizedOverlayColor = computed(() => getSectionContent(props.template, "hero", "overlay_color", "").trim());
const primaryLink = computed(() => getSectionContent(props.template, "hero", "button_link", route("frontend.products")));
</script>

<template>
    <section
        class="relative isolate overflow-hidden py-14 md:py-20 lg:py-24"
        :class="hasBackgroundImage ? '' : 'bg-foreground'"
    >
        <!-- Background Image -->
        <div v-if="hasBackgroundImage" class="absolute inset-0">
            <img :src="imageUrl" :alt="title" class="h-full w-full object-cover" />
            <div v-if="normalizedOverlayColor" class="absolute inset-0" :style="{ backgroundColor: normalizedOverlayColor }"></div>
        </div>

        <div class="container mx-auto px-4">
            <div class="relative grid min-h-[24rem] items-end md:min-h-[30rem] md:grid-cols-12">
                <!-- Content -->
                <div class="max-w-4xl pb-2 md:col-span-9" :class="hasBackgroundImage ? 'relative z-10' : ''">
                    <!-- Badge -->
                    <div
                        v-if="badge"
                        class="mb-6 inline-flex border px-3 py-1.5 text-xs font-semibold tracking-[0.12em] uppercase"
                        :class="hasBackgroundImage ? 'border-white/30 text-white/90' : 'border-white/25 text-white/80'"
                    >
                        <span>{{ badge }}</span>
                    </div>

                    <!-- Heading -->
                    <h1 class="font-display mb-5 max-w-4xl text-4xl leading-[1.04] text-balance text-white sm:text-5xl md:text-6xl lg:text-7xl">
                        {{ title }}
                    </h1>

                    <!-- Subheading -->
                    <p
                        class="mb-9 max-w-xl text-base leading-relaxed md:text-lg"
                        :class="hasBackgroundImage ? 'text-white/85' : 'text-white/70'"
                    >
                        {{ subtitle }}
                    </p>

                    <!-- CTA Button -->
                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="primaryLink"
                            class="group inline-flex min-h-12 items-center gap-2 border border-white bg-white px-6 py-3 text-sm font-semibold text-foreground transition-colors duration-200 hover:bg-transparent hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-foreground motion-reduce:transition-none"
                        >
                            {{ buttonText }}
                            <ChevronRight class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5 motion-reduce:transition-none" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
