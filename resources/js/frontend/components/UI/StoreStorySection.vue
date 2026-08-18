<script setup>
import { computed } from "vue";
import { ArrowUpRight } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";

const props = defineProps({
    template: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const { t } = useI18n();
const eyebrow = computed(() => getSectionContent(props.template, "store_story", "eyebrow", t("labels.home_story.eyebrow")));
const title = computed(() => getSectionContent(props.template, "store_story", "title", t("labels.home_story.title")));
const description = computed(() => getSectionContent(props.template, "store_story", "description", t("labels.home_story.description")));
const actionLabel = computed(() => t("labels.home_story.action"));
const items = computed(() => {
    const configuredItems = getSectionContent(props.template, "store_story", "items", null);

    if (Array.isArray(configuredItems) && configuredItems.length > 0) {
        return configuredItems.map((item) => ({
            ...item,
            title: item.title || item.name,
            href: item.href || route("frontend.home", item.slug ? { category: item.slug } : {}),
        }));
    }

    return props.categories.slice(0, 3).map((category) => ({
        id: category.id,
        title: category.name,
        image_url: category.image_url,
        href: route("frontend.home", { category: category.slug }),
    }));
});
</script>

<template>
    <section v-if="items.length" class="overflow-hidden bg-secondary py-16 text-foreground md:py-28">
        <div class="container mx-auto px-4">
            <div class="grid gap-12 md:grid-cols-12 md:gap-16">
                <div class="flex flex-col justify-between border-t border-foreground/10 pt-6 md:col-span-4 md:pb-2">
                    <div>
                        <p class="mb-5 text-xs font-semibold tracking-[0.12em] text-primary uppercase">{{ eyebrow }}</p>
                        <h2 class="max-w-md text-4xl font-bold leading-[0.98] text-balance md:text-6xl">{{ title }}</h2>
                    </div>
                    <p class="mt-8 max-w-sm text-sm leading-6 text-pretty text-muted-foreground md:text-base">{{ description }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-12 md:col-span-8 md:gap-4">
                    <Link
                        v-for="(item, index) in items"
                        :key="item.id || item.slug || item.title"
                        :href="item.href"
                        :aria-label="`${actionLabel}: ${item.title}`"
                        class="group relative flex min-h-52 overflow-hidden border border-foreground/10 bg-background p-5 transition-[border-color,background-color] duration-300 hover:border-primary hover:bg-background/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-secondary motion-reduce:transition-none"
                        :class="[
                            index === 0 ? 'sm:col-span-7 sm:row-span-2 sm:min-h-[35rem] sm:p-8' : 'sm:col-span-5 sm:min-h-[16.5rem] sm:p-6',
                        ]"
                    >
                        <img
                            v-if="item.image_url"
                            :src="item.image_url"
                            :alt="item.title"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] motion-reduce:transition-none"
                        />
                        <div v-if="item.image_url" class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/5"></div>

                        <div class="relative flex w-full flex-col justify-end" :class="item.image_url ? 'text-white' : 'text-foreground'">
                            <span class="font-bold leading-[1.05]" :class="index === 0 ? 'max-w-sm text-2xl md:text-3xl' : 'text-base'">{{ item.title }}</span>
                            <span v-if="item.description" class="mt-2 max-w-md text-sm leading-5" :class="item.image_url ? 'text-white/85' : 'text-muted-foreground'">
                                {{ item.description }}
                            </span>
                            <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold">
                                {{ actionLabel }}
                                <ArrowUpRight class="h-4 w-4 transition-transform duration-200 ease-out group-hover:-translate-y-0.5 group-hover:translate-x-0.5 motion-reduce:transition-none" />
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
