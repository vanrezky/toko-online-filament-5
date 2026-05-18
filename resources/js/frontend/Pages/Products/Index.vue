<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import ProductCard from "../../components/UI/ProductCard.vue";
import { Search, X, Loader2, SlidersHorizontal } from "lucide-vue-next";
import debounce from "lodash/debounce";
import { useTranslations } from "../../composables/useTranslations";

const { t } = useTranslations();

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters.search || "");
const selectedCategory = ref(props.filters.category || "");
const selectedSort = ref(props.filters.sort || "newest");
const priceMin = ref(props.filters.price_min || "");
const priceMax = ref(props.filters.price_max || "");
const isFilterOpen = ref(false);
const isLoadingMore = ref(false);
const allProducts = ref([...props.products.data]);

onMounted(() => {
    applyFilters();
});

watch(
    () => props.products.data,
    (newData) => {
        if (!isLoadingMore.value) {
            allProducts.value = [...newData];
        }
    },
);

watch([search, selectedCategory, selectedSort, priceMin, priceMax], () => {
    applyFilters();
});

const hasActiveFilters = computed(() => {
    return search.value || selectedCategory.value || selectedSort.value !== "newest" || priceMin.value || priceMax.value;
});

const applyFilters = debounce(() => {
    if (priceMin.value && parseFloat(priceMin.value) < 0) {
        priceMin.value = "";
    }
    if (priceMax.value && parseFloat(priceMax.value) < 0) {
        priceMax.value = "";
    }

    router.get(
        route("frontend.products"),
        {
            search: search.value || null,
            category: selectedCategory.value || null,
            sort: selectedSort.value,
            price_min: priceMin.value || null,
            price_max: priceMax.value || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                isLoadingMore.value = false;
            },
        },
    );
}, 300);

const loadMore = () => {
    if (props.products.links.next && !isLoadingMore.value) {
        isLoadingMore.value = true;
        router.get(
            props.products.links.next,
            {
                search: search.value,
                category: selectedCategory.value,
                sort: selectedSort.value,
                price_min: priceMin.value,
                price_max: priceMax.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ["products"],
                onSuccess: (page) => {
                    allProducts.value = [...allProducts.value, ...page.props.products.data];
                    isLoadingMore.value = false;
                },
                onFinish: () => {
                    isLoadingMore.value = false;
                },
            },
        );
    }
};

const resetFilters = () => {
    search.value = "";
    selectedCategory.value = "";
    selectedSort.value = "newest";
    priceMin.value = "";
    priceMax.value = "";

    applyFilters();
};

const totalProducts = computed(() => props.products.total || allProducts.value.length);
</script>

<template>
    <TemplateWrapper :title="t('meta.products.title')">
        <div class="bg-secondary/30 py-8 md:py-12">
            <div class="container mx-auto px-4">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-foreground md:text-3xl">{{ t('labels.products.default_title') }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">{{ t('labels.products.available_count', { count: totalProducts }) }}</p>
                    </div>

                    <button
                        @click="isFilterOpen = !isFilterOpen"
                        class="flex items-center justify-center gap-2 rounded-lg border border-border bg-white px-4 py-2.5 text-sm font-medium md:hidden"
                    >
                        <SlidersHorizontal class="h-4 w-4" />
                        {{ t('labels.actions.filter') }}
                        <span
                            v-if="hasActiveFilters"
                            class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground"
                            >!</span
                        >
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
                    <!-- Filters Sidebar -->
                    <aside :class="['rounded-xl bg-white p-6', isFilterOpen ? 'block' : 'hidden md:block']">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-lg font-semibold">{{ t('labels.products.filter_and_sort') }}</h3>
                            <button v-if="hasActiveFilters" @click="resetFilters" class="text-xs font-medium text-primary hover:underline">
                                {{ t('labels.actions.reset') }}
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="mb-6">
                            <label class="mb-2 block text-sm font-medium">{{ t('labels.form.search') }}</label>
                            <div class="relative">
                                <input
                                    v-model="search"
                                    type="text"
                                    :placeholder="t('placeholders.search_products')"
                                    class="w-full rounded-lg border border-border bg-secondary py-2.5 pl-10 pr-4 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                                <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
                                <button
                                    v-if="search"
                                    @click="search = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Sort -->
                        <div class="mb-6">
                            <label class="mb-2 block text-sm font-medium">{{ t('labels.form.sort') }}</label>
                            <select
                                v-model="selectedSort"
                                class="w-full cursor-pointer rounded-lg border border-border bg-secondary px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                                <option value="newest">{{ t('labels.sort.newest') }}</option>
                                <option value="price_low">{{ t('labels.sort.price_low_high') }}</option>
                                <option value="price_high">{{ t('labels.sort.price_high_low') }}</option>
                                <option value="name_asc">{{ t('labels.sort.name_asc') }}</option>
                                <option value="name_desc">{{ t('labels.sort.name_desc') }}</option>
                            </select>
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <h4 class="mb-3 text-sm font-semibold">{{ t('labels.form.category') }}</h4>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input type="radio" v-model="selectedCategory" value="" class="h-4 w-4 accent-primary" />
                                    <span class="text-sm">{{ t('labels.filters.all_categories') }}</span>
                                </label>
                                <label v-for="category in categories" :key="category.id" class="flex cursor-pointer items-center gap-2">
                                    <input type="radio" v-model="selectedCategory" :value="category.slug" class="h-4 w-4 accent-primary" />
                                    <span class="text-sm">{{ category.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="mb-6">
                            <h4 class="mb-3 text-sm font-semibold">{{ t('labels.form.price') }}</h4>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="priceMin"
                                    type="number"
                                    min="0"
                                    :placeholder="t('placeholders.price_min')"
                                    class="w-full rounded-lg border border-border bg-secondary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                                <span class="text-muted-foreground">-</span>
                                <input
                                    v-model="priceMax"
                                    type="number"
                                    min="0"
                                    :placeholder="t('placeholders.price_max')"
                                    class="w-full rounded-lg border border-border bg-secondary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                        </div>

                        <!-- Active Filters Tags -->
                        <div v-if="hasActiveFilters" class="border-t border-border pt-4">
                            <h4 class="mb-3 text-sm font-semibold">{{ t('labels.filters.active') }}</h4>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-if="search"
                                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                                >
                                    "{{ search }}"
                                    <button @click="search = ''" class="hover:text-primary/70">
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                                <span
                                    v-if="selectedCategory"
                                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                                >
                                    {{ categories.find((c) => c.slug === selectedCategory)?.name }}
                                    <button @click="selectedCategory = ''" class="hover:text-primary/70">
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                                <span
                                    v-if="priceMin || priceMax"
                                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                                >
                                    Rp{{ priceMin || "0" }} - Rp{{ priceMax || "∞" }}
                                    <button
                                        @click="
                                            priceMin = '';
                                            priceMax = '';
                                        "
                                        class="hover:text-primary/70"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                        </div>
                    </aside>

                    <!-- Product Grid -->
                    <div>
                        <div v-if="allProducts.length > 0" class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
                            <ProductCard v-for="product in allProducts" :key="product.uuid || product.id" :product="product" />
                        </div>

                        <div v-else class="py-20 text-center">
                            <div class="mb-4 text-6xl">📭</div>
                            <h3 class="mb-2 text-xl font-bold text-foreground">{{ t('labels.products.not_found') }}</h3>
                            <p class="mb-6 text-sm text-muted-foreground">{{ t('labels.products.adjust_filters') }}</p>
                            <button
                                @click="resetFilters"
                                class="inline-block rounded-full bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                {{ t('labels.actions.reset_filters') }}
                            </button>
                        </div>

                        <!-- Load More -->
                        <div v-if="products.links.next" class="flex justify-center pt-12">
                            <button
                                @click="loadMore"
                                :disabled="isLoadingMore"
                                class="flex min-w-[200px] items-center justify-center gap-2 rounded-full bg-white px-8 py-3 text-sm font-semibold text-foreground shadow-sm transition-colors hover:bg-primary hover:text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2 v-if="isLoadingMore" class="h-4 w-4 animate-spin" />
                                <span>{{ isLoadingMore ? t('labels.actions.loading') : t('labels.actions.load_more') }}</span>
                            </button>
                        </div>
                        <div v-else-if="allProducts.length > 0" class="pt-12 text-center">
                            <p class="text-sm text-muted-foreground">{{ t('labels.products.all_viewed') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TemplateWrapper>
</template>
