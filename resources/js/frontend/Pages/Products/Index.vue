<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import FormRadio from "../../components/UI/FormRadio.vue";
import FormSelect from "../../components/UI/FormSelect.vue";
import ProductCard from "../../components/UI/ProductCard.vue";
import Card from "../../components/UI/Card.vue";
import { Search, X, Loader2, SlidersHorizontal } from "lucide-vue-next";
import debounce from "lodash/debounce";
import { useI18n } from "vue-i18n";
import { formatCompactNumber } from "../../lib/utils";

const { t, locale } = useI18n();

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

const hasActiveFilters = computed(() => Boolean(search.value || selectedCategory.value || selectedSort.value !== "newest" || priceMin.value || priceMax.value));

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
const formatPriceRange = (amount) => `Rp${formatCompactNumber(amount, locale.value).replace(/\+$/, "")}`;

const priceRanges = computed(() => [
    { key: "under_25", min: "", max: 25000, label: t("labels.filters.price_under", { amount: formatPriceRange(25000) }) },
    { key: "25_50", min: 25000, max: 50000, label: t("labels.filters.price_between", { min: formatPriceRange(25000), max: formatPriceRange(50000) }) },
    { key: "50_100", min: 50000, max: 100000, label: t("labels.filters.price_between", { min: formatPriceRange(50000), max: formatPriceRange(100000) }) },
    { key: "over_100", min: 100000, max: "", label: t("labels.filters.price_over", { amount: formatPriceRange(100000) }) },
]);

const selectedPriceRange = computed(() => {
    return priceRanges.value.find((range) => String(range.min) === String(priceMin.value) && String(range.max) === String(priceMax.value))?.key || "";
});

const selectPriceRange = (range) => {
    priceMin.value = range.min;
    priceMax.value = range.max;
};
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('meta.products.title')">
        <PageShell container :title="t('labels.products.default_title')" class="pb-12 md:pb-16">
            <template #actions>
                <Button
                    @click="isFilterOpen = !isFilterOpen"
                    :aria-expanded="isFilterOpen"
                    aria-controls="product-filters"
                    class="border-border flex items-center justify-center gap-2 rounded-lg border bg-white px-4 py-2.5 text-sm font-medium md:hidden"
                >
                    <SlidersHorizontal class="h-4 w-4" />
                    {{ t("labels.actions.filter") }}
                    <span
                        v-if="hasActiveFilters"
                        class="bg-primary text-primary-foreground flex h-5 w-5 items-center justify-center rounded-full text-[10px]"
                        >!</span
                    >
                </Button>
            </template>
            <div>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
                    <!-- Filters Sidebar -->
                    <Card
                        id="product-filters"
                        as="aside"
                        :class="['rounded-2xl border p-5 shadow-sm lg:sticky lg:top-20 lg:self-start', isFilterOpen ? 'block' : 'hidden md:block']"
                    >
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-lg font-semibold">{{ t("labels.products.filter_and_sort") }}</h3>
                            <Button v-if="hasActiveFilters" @click="resetFilters" class="text-primary text-xs font-medium hover:underline">
                                {{ t("labels.actions.reset") }}
                            </Button>
                        </div>

                        <!-- Search -->
                        <div class="mb-6">
                            <label class="mb-2 block text-sm font-medium">{{ t("labels.form.search") }}</label>
                            <FormInput v-model="search" type="text" :placeholder="t('placeholders.search_products')" class="py-2.5">
                                <template #prefix><Search class="text-muted-foreground absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2" /></template>
                                <template #suffix
                                    ><Button
                                        v-if="search"
                                        @click="search = ''"
                                        class="text-muted-foreground hover:text-foreground absolute top-1/2 right-3 -translate-y-1/2"
                                    >
                                        <X class="h-4 w-4" /></button
                                ></template>
                            </FormInput>
                        </div>

                        <!-- Sort -->
                        <div class="mb-6">
                            <label class="mb-2 block text-sm font-medium">{{ t("labels.form.sort") }}</label>
                            <FormSelect v-model="selectedSort" class="cursor-pointer">
                                <option value="newest">{{ t("labels.sort.newest") }}</option>
                                <option value="price_low">{{ t("labels.sort.price_low_high") }}</option>
                                <option value="price_high">{{ t("labels.sort.price_high_low") }}</option>
                                <option value="name_asc">{{ t("labels.sort.name_asc") }}</option>
                                <option value="name_desc">{{ t("labels.sort.name_desc") }}</option>
                            </FormSelect>
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <h4 class="mb-3 text-sm font-semibold">{{ t("labels.form.category") }}</h4>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <FormRadio v-model="selectedCategory" value="" />
                                    <span class="text-sm">{{ t("labels.filters.all_categories") }}</span>
                                </label>
                                <label v-for="category in categories" :key="category.id" class="flex cursor-pointer items-center gap-2">
                                    <FormRadio v-model="selectedCategory" :value="category.slug" />
                                    <span class="text-sm">{{ category.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="mb-6">
                            <h4 class="mb-3 text-sm font-semibold">{{ t("labels.form.price") }}</h4>
                            <div class="flex items-center gap-2">
                                <FormInput v-model="priceMin" type="number" min="0" :placeholder="t('placeholders.price_min')" class="py-2" />
                                <span class="text-muted-foreground">-</span>
                                <FormInput v-model="priceMax" type="number" min="0" :placeholder="t('placeholders.price_max')" class="py-2" />
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <Button
                                    v-for="range in priceRanges"
                                    :key="range.key"
                                    type="button"
                                    @click="selectPriceRange(range)"
                                    class="border-border text-muted-foreground hover:border-primary hover:text-primary rounded-md border px-1.5 py-1.5 text-xs transition-colors"
                                    :class="{ 'border-primary bg-primary/10 text-primary font-semibold': selectedPriceRange === range.key }"
                                >
                                    {{ range.label }}
                                </Button>
                            </div>
                        </div>

                        <!-- Active Filters Tags -->
                        <div v-if="hasActiveFilters" class="border-border mt-4 border-t pt-4">
                            <h4 class="mb-3 text-sm font-semibold">{{ t("labels.filters.active") }}</h4>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-if="search"
                                    class="bg-primary/10 text-primary inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium"
                                >
                                    "{{ search }}"
                                    <Button @click="search = ''" class="hover:text-primary/70">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </span>
                                <span
                                    v-if="selectedCategory"
                                    class="bg-primary/10 text-primary inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium"
                                >
                                    {{ categories.find((c) => c.slug === selectedCategory)?.name }}
                                    <Button @click="selectedCategory = ''" class="hover:text-primary/70">
                                        <X class="h-3 w-3" />
                                    </Button>
                                </span>
                                <span
                                    v-if="priceMin || priceMax"
                                    class="bg-primary/10 text-primary inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium"
                                >
                                    Rp{{ priceMin || "0" }} - Rp{{ priceMax || "∞" }}
                                    <Button
                                        @click="
                                            priceMin = '';
                                            priceMax = '';
                                        "
                                        class="hover:text-primary/70"
                                    >
                                        <X class="h-3 w-3" />
                                    </Button>
                                </span>
                            </div>
                        </div>
                    </Card>

                    <!-- Product Grid -->
                    <div aria-live="polite">
                        <div v-if="allProducts.length > 0" class="grid grid-cols-2 gap-x-4 gap-y-6 sm:gap-x-5 sm:gap-y-8 lg:grid-cols-4">
                            <ProductCard v-for="product in allProducts" :key="product.uuid || product.id" :product="product" />
                        </div>

                        <div v-else class="rounded-2xl border border-dashed border-border bg-white px-6 py-20 text-center">
                            <div class="mb-4 text-6xl">📭</div>
                            <h3 class="text-foreground mb-2 text-xl font-bold">{{ t("labels.products.not_found") }}</h3>
                            <p class="text-muted-foreground mb-6 text-sm">{{ t("labels.products.adjust_filters") }}</p>
                            <Button
                                @click="resetFilters"
                                class="bg-primary text-primary-foreground hover:bg-primary/90 inline-block rounded-full px-6 py-3 text-sm font-semibold transition-colors"
                            >
                                {{ t("labels.actions.reset_filters") }}
                            </Button>
                        </div>

                        <!-- Load More -->
                        <div v-if="products.links.next" class="flex justify-center pt-10 sm:pt-12">
                            <Button
                                @click="loadMore"
                                :disabled="isLoadingMore"
                                variant="outline"
                                class="flex min-w-[200px] items-center justify-center gap-2 rounded-full disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Loader2 v-if="isLoadingMore" class="h-4 w-4 animate-spin" />
                                <span>{{ isLoadingMore ? t("labels.actions.loading") : t("labels.actions.load_more") }}</span>
                            </Button>
                        </div>
                        <div v-else-if="allProducts.length > 0" class="pt-12 text-center">
                            <p class="text-muted-foreground text-sm">{{ t("labels.products.all_viewed") }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
