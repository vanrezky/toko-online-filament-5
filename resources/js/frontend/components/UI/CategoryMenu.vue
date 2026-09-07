<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    Baby,
    BookOpen,
    CarFront,
    Coffee,
    Dumbbell,
    Gamepad2,
    Grid2X2,
    HeartPulse,
    Home,
    Laptop,
    Shirt,
    Sparkles,
    ChevronRight,
} from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { getSectionContent } from "../../lib/utils";

const props = defineProps({
    categories: { type: Array, default: () => [] },
    activeCategory: { type: String, default: "" },
    template: { type: Object, default: null },
});

const page = usePage();
const { t } = useI18n();
const failedCategoryImages = ref(new Set());
const categoryIconsBySlug = {
    clothes: Shirt,
    pants: Shirt,
    jewellery: Sparkles,
    elektronik: Laptop,
    "fashion-pria": Shirt,
    "fashion-wanita": Sparkles,
    "kebutuhan-rumah": Home,
    "kesehatan-kecantikan": HeartPulse,
    olahraga: Dumbbell,
    buku: BookOpen,
    kuliner: Coffee,
    otomotif: CarFront,
    anak: Baby,
    game: Gamepad2,
};
const sectionTitle = computed(() => getSectionContent(props.template, "category_menu", "title", t("labels.home.category_title")));
const showAll = computed(() => {
    const value = getSectionContent(props.template, "category_menu", "show_all", "1");

    return ![false, 0, "0", "false"].includes(value);
});

const allCategories = computed(() => {
    if (props.categories.length > 0) return props.categories;

    return page.props.categories || [];
});

const categoryImageUrl = (category) => {
    const imageUrl = category.image_url;

    if (!imageUrl) return "";

    return imageUrl.replace(/^https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?/i, "");
};

const hasCategoryImage = (category) => categoryImageUrl(category) && !failedCategoryImages.value.has(category.slug);

const handleCategoryImageError = (category) => {
    failedCategoryImages.value = new Set([...failedCategoryImages.value, category.slug]);
};

const categoryIcon = (category) => categoryIconsBySlug[category.slug] || Sparkles;
</script>

<template>
    <section class="bg-background py-4 md:py-6" aria-labelledby="popular-categories-title">
        <div class="container mx-auto px-4 md:px-8">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 id="popular-categories-title" class="text-foreground text-xl font-bold tracking-[-0.03em] md:text-2xl">{{ sectionTitle }}</h2>
                <Link
                    v-if="showAll"
                    :href="route('frontend.products')"
                    class="text-primary hover:text-primary/75 focus-visible:ring-primary inline-flex min-h-11 items-center gap-1 rounded-lg px-2 text-xs font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none sm:text-sm"
                >
                    <span class="hidden sm:inline">{{ t("labels.home.category_view_all") }}</span>
                    <span class="sm:hidden">{{ t("labels.actions.view_all") }}</span>
                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>

            <div class="scrollbar-hidden -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:gap-3 md:mx-0 md:grid md:grid-cols-6 md:overflow-visible md:px-0 lg:grid-cols-8 xl:grid-cols-12">
                <Link
                    :href="route('frontend.home')"
                    class="group flex min-w-[5.75rem] flex-1 flex-col items-center justify-center gap-2 rounded-2xl border px-2 py-3 text-center transition-[background-color,border-color,transform] hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none motion-reduce:transform-none motion-reduce:transition-none md:min-w-0"
                    :class="!activeCategory ? 'border-primary bg-primary text-primary-foreground shadow-[0_12px_24px_-20px_hsl(var(--primary)/0.9)]' : 'border-border bg-secondary/45 text-foreground hover:border-primary/40 hover:bg-secondary'"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-full" :class="!activeCategory ? 'bg-white/20' : 'bg-primary/10 text-primary'">
                        <Grid2X2 class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span class="max-w-full truncate text-[11px] font-semibold">{{ t("labels.filters.all_categories") }}</span>
                </Link>

                <Link
                    v-for="category in allCategories"
                    :key="category.id || category.slug"
                    :href="route('frontend.home', { category: category.slug })"
                    class="group flex min-w-[5.75rem] flex-1 flex-col items-center justify-center gap-2 rounded-2xl border px-2 py-3 text-center transition-[background-color,border-color,transform] hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none motion-reduce:transform-none motion-reduce:transition-none md:min-w-0"
                    :class="activeCategory === category.slug ? 'border-primary bg-primary text-primary-foreground shadow-[0_12px_24px_-20px_hsl(var(--primary)/0.9)]' : 'border-border bg-secondary/45 text-foreground hover:border-primary/40 hover:bg-secondary'"
                >
                    <span class="relative flex h-10 w-10 items-center justify-center overflow-hidden rounded-full" :class="activeCategory === category.slug ? 'bg-white/20' : 'bg-primary/10 text-primary'">
                        <img
                            v-if="hasCategoryImage(category)"
                            :src="categoryImageUrl(category)"
                            :alt="category.name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                            @error="handleCategoryImageError(category)"
                        />
                        <component v-else :is="categoryIcon(category)" class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span class="max-w-full truncate text-[11px] font-semibold">{{ category.name }}</span>
                </Link>
            </div>
        </div>
    </section>
</template>
