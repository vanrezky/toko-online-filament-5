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
    <section v-if="items.length" class="border-y border-border bg-secondary/40 py-14 md:py-20">
        <div class="container mx-auto px-4">
            <div class="grid items-end gap-9 md:grid-cols-12 md:gap-12">
                <div class="md:col-span-5">
                    <p class="mb-3 text-xs font-semibold tracking-[0.12em] text-primary uppercase">{{ eyebrow }}</p>
                    <h2 class="text-foreground max-w-md text-3xl font-bold leading-[1.1] text-balance md:text-4xl">{{ title }}</h2>
                    <p class="text-muted-foreground mt-4 max-w-md text-sm leading-6 text-pretty md:text-base">{{ description }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:col-span-7 md:gap-4">
                    <Link
                        v-for="(item, index) in items"
                        :key="item.id || item.slug || item.title"
                        :href="item.href"
                        :aria-label="`${actionLabel}: ${item.title}`"
                        class="group relative flex min-h-52 overflow-hidden border border-border bg-background p-5 transition-[border-color,background-color] duration-200 hover:border-primary/50 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 motion-reduce:transition-none"
                        :class="index === 0 ? 'sm:col-span-2 sm:min-h-72' : ''"
                    >
                        <img
                            v-if="item.image_url"
                            :src="item.image_url"
                            :alt="item.title"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] motion-reduce:transition-none"
                        />
                        <div v-if="item.image_url" class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/30 to-black/5"></div>

                        <div class="relative flex w-full flex-col justify-end" :class="item.image_url ? 'text-white' : 'text-foreground'">
                            <span class="text-base font-bold leading-snug">{{ item.title }}</span>
                            <span v-if="item.description" class="mt-1 max-w-md text-sm leading-5" :class="item.image_url ? 'text-white/90' : 'text-muted-foreground'">
                                {{ item.description }}
                            </span>
                            <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold" :class="item.image_url ? 'text-white' : 'text-primary'">
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
