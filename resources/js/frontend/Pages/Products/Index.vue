<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import FormSelect from "../../components/UI/FormSelect.vue";
import ProductCard from "../../components/UI/ProductCard.vue";
import ProductFilters from "../../components/UI/ProductFilters.vue";
import Card from "../../components/UI/Card.vue";
import { SlidersHorizontal, X, ChevronLeft, ChevronRight, Loader2 } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { formatCompactNumber } from "../../lib/utils";

const { t, locale } = useI18n();

const props = defineProps({
    products: { type: Object, default: () => ({ data: [], meta: {}, links: {} }) },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const variantKeys = ["color", "size", "gender"];
const isFilterOpen = ref(false);
const isLoading = ref(false);

const toList = (value) => {
    if (Array.isArray(value)) {
        return value.flatMap((item) => String(item).split(",")).map((item) => item.trim()).filter(Boolean);
    }

    return value === null || value === undefined || value === "" ? [] : String(value).split(",").map((item) => item.trim()).filter(Boolean);
};

const createFilterState = (source = {}) => ({
    search: source.search || "",
    categories: toList(source.category),
    sort: source.sort || "newest",
    price_min: source.price_min ?? "",
    price_max: source.price_max ?? "",
    rating_min: source.rating_min ?? "",
    promos: toList(source.promo),
    per_page: Number(source.per_page || 12),
    variant_color: toList(source.variant_color),
    variant_size: toList(source.variant_size),
    variant_gender: toList(source.variant_gender),
});

const cloneState = (state) => JSON.parse(JSON.stringify(state));
const appliedFilters = ref(createFilterState(props.filters));
const draftFilters = ref(createFilterState(props.filters));

watch(
    () => props.filters,
    (nextFilters) => {
        appliedFilters.value = createFilterState(nextFilters);
        if (!isFilterOpen.value) {
            draftFilters.value = cloneState(appliedFilters.value);
        }
    },
    { deep: true },
);

const formatPrice = (amount) => "Rp" + formatCompactNumber(amount, locale.value).replace(/\+$/, "");
const categoryName = (slug) => props.categories.find((category) => category.slug === slug)?.name || slug;

const activeFilterChips = computed(() => {
    const state = appliedFilters.value;
    const chips = [];

    if (state.search) chips.push({ key: "search", value: state.search, label: '"' + state.search + '"' });
    state.categories.forEach((value) => chips.push({ key: "categories", value, label: categoryName(value) }));
    if (state.price_min || state.price_max) {
        chips.push({ key: "price", value: "price", label: formatPrice(state.price_min || 0) + " - " + (state.price_max ? formatPrice(state.price_max) : "∞") });
    }
    if (state.rating_min) chips.push({ key: "rating_min", value: state.rating_min, label: t("labels.filters.rating_chip", { rating: state.rating_min }) });
    state.promos.forEach((value) => chips.push({ key: "promos", value, label: t("labels.filters.promo_" + value) }));

    variantKeys.forEach((key) => {
        (state["variant_" + key] || []).forEach((value) => {
            chips.push({ key: "variant_" + key, value, label: value });
        });
    });

    return chips;
});

const hasActiveFilters = computed(() => activeFilterChips.value.length > 0);
const totalProducts = computed(() => props.products?.meta?.total ?? props.products?.total ?? 0);
const pageLinks = computed(() => (props.products?.meta?.links || []).filter((link) => /^\d+$/.test(String(link.label).replace(/&hellip;|<[^>]+>/g, ""))));
const previousPage = computed(() => props.products?.links?.prev || null);
const nextPage = computed(() => props.products?.links?.next || null);
const rangeStart = computed(() => props.products?.meta?.from || 0);
const rangeEnd = computed(() => props.products?.meta?.to || 0);

const buildQuery = (state) => {
    const query = {};
    const compactList = (values) => values.length === 1 ? values[0] : values;

    if (state.search) query.search = state.search;
    if (state.categories.length) query.category = compactList(state.categories);
    if (state.sort !== "newest") query.sort = state.sort;
    if (state.price_min) query.price_min = state.price_min;
    if (state.price_max) query.price_max = state.price_max;
    if (state.rating_min) query.rating_min = state.rating_min;
    if (state.promos.length) query.promo = compactList(state.promos);
    if (Number(state.per_page) !== 12) query.per_page = Number(state.per_page);

    variantKeys.forEach((key) => {
        if (state["variant_" + key]?.length) {
            query["variant_" + key] = compactList(state["variant_" + key]);
        }
    });

    return query;
};

const applyFilters = () => {
    const state = cloneState(draftFilters.value);
    appliedFilters.value = state;
    isLoading.value = true;

    router.get(route("frontend.products"), buildQuery(state), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            isLoading.value = true;
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const updateSort = (value) => {
    draftFilters.value = { ...draftFilters.value, sort: value || "newest" };
    applyFilters();
};

const handleFilterChange = () => {
    if (!isFilterOpen.value) applyFilters();
};

const openMobileFilters = () => {
    draftFilters.value = cloneState(appliedFilters.value);
    isFilterOpen.value = true;
};

const applyMobileFilters = () => {
    applyFilters();
    isFilterOpen.value = false;
};

const resetFilters = () => {
    draftFilters.value = createFilterState();
    applyFilters();
    isFilterOpen.value = false;
};

const removeChip = (chip) => {
    const next = cloneState(appliedFilters.value);

    if (chip.key === "price") {
        next.price_min = "";
        next.price_max = "";
    } else if (chip.key === "rating_min" || chip.key === "search") {
        next[chip.key] = "";
    } else {
        next[chip.key] = (next[chip.key] || []).filter((value) => value !== chip.value);
    }

    draftFilters.value = next;
    applyFilters();
};

const goToPage = (url) => {
    if (!url || isLoading.value) return;

    isLoading.value = true;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const pageSize = computed({
    get: () => props.filters.per_page || 12,
    set: (value) => {
        draftFilters.value = { ...draftFilters.value, per_page: Number(value) };
        applyFilters();
    },
});
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('meta.products.title')">
        <PageShell container :title="t('labels.products.default_title')" :description="t('labels.products.description')" class="pb-12 md:pb-16">
            <div class="mb-5 flex flex-col gap-4">
                <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 rounded-2xl bg-white p-3 shadow-sm">
                    <span class="text-xs font-semibold text-foreground">{{ t("labels.filters.active") }}:</span>
                    <span
                        v-for="chip in activeFilterChips"
                        :key="chip.key + '-' + chip.value"
                        class="bg-primary/10 text-primary inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-medium"
                    >
                        {{ chip.label }}
                        <button type="button" class="hover:text-primary/60" :aria-label="t('labels.filters.remove', { label: chip.label })" @click="removeChip(chip)">
                            <X class="h-3 w-3" aria-hidden="true" />
                        </button>
                    </span>
                    <button type="button" class="text-primary ml-auto text-xs font-medium underline" @click="resetFilters">
                        {{ t("labels.actions.reset_filters") }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
                <Card as="aside" class="hidden rounded-2xl border p-5 shadow-sm lg:sticky lg:top-20 lg:block lg:self-start">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-foreground">{{ t("labels.products.filter_and_sort") }}</h2>
                        <button v-if="hasActiveFilters" type="button" class="text-primary text-xs font-medium hover:underline" @click="resetFilters">
                            {{ t("labels.actions.reset") }}
                        </button>
                    </div>
                    <ProductFilters v-model="draftFilters" id-prefix="desktop-filter" :categories="categories" @change="handleFilterChange" />
                </Card>

                <section aria-live="polite" class="min-w-0">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-muted-foreground">
                            {{ t("labels.products.result_count", { from: rangeStart, to: rangeEnd, total: totalProducts }) }}
                        </p>
                        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                            <button
                                type="button"
                                class="border-primary bg-primary/5 text-primary inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold lg:hidden"
                                :aria-expanded="isFilterOpen"
                                aria-controls="mobile-product-filters"
                                @click="openMobileFilters"
                            >
                                <SlidersHorizontal class="h-4 w-4" aria-hidden="true" />
                                {{ t("labels.actions.filter") }}
                                <span v-if="hasActiveFilters" class="bg-primary text-primary-foreground flex h-5 w-5 items-center justify-center rounded-full text-[10px]">
                                    {{ activeFilterChips.length }}
                                </span>
                            </button>
                            <label class="sr-only" for="product-sort">{{ t("labels.form.sort") }}</label>
                            <FormSelect id="product-sort" :model-value="draftFilters.sort || 'newest'" class="w-40 sm:w-44" @update:model-value="updateSort">
                                <option value="newest">{{ t("labels.sort.newest") }}</option>
                                <option value="price_low">{{ t("labels.sort.price_low_high") }}</option>
                                <option value="price_high">{{ t("labels.sort.price_high_low") }}</option>
                                <option value="name_asc">{{ t("labels.sort.name_asc") }}</option>
                                <option value="name_desc">{{ t("labels.sort.name_desc") }}</option>
                            </FormSelect>
                            <label class="sr-only" for="product-page-size">{{ t("labels.products.page_size") }}</label>
                            <FormSelect id="product-page-size" v-model="pageSize" class="w-32">
                                <option :value="12">12</option>
                                <option :value="24">24</option>
                                <option :value="36">36</option>
                            </FormSelect>
                        </div>
                    </div>

                    <div v-if="products.data?.length" class="grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        <ProductCard v-for="product in products.data" :key="product.uuid || product.id" :product="product" />
                    </div>

                    <div v-else class="relative overflow-hidden rounded-3xl border border-dashed border-border bg-white py-20 text-center">
                        <div class="relative z-10">
                            <div class="mb-4 text-6xl">📭</div>
                            <h2 class="text-foreground mb-2 text-xl font-bold">{{ t("labels.products.not_found") }}</h2>
                            <p class="text-muted-foreground mb-6 text-sm">{{ t("labels.products.adjust_filters") }}</p>
                            <Button type="button" class="bg-primary text-primary-foreground rounded-full px-6 py-3 text-sm font-semibold" @click="resetFilters">
                                {{ t("labels.actions.reset_filters") }}
                            </Button>
                        </div>
                    </div>

                    <div v-if="products.data?.length && (pageLinks.length > 1 || previousPage || nextPage)" class="mt-10 flex flex-wrap items-center justify-center gap-2" :aria-label="t('labels.products.pagination')">
                        <button
                            type="button"
                            class="border-border text-foreground inline-flex h-10 w-10 items-center justify-center rounded-xl border disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!previousPage || isLoading"
                            @click="goToPage(previousPage)"
                        >
                            <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                        </button>
                        <Link
                            v-for="link in pageLinks"
                            :key="link.label"
                            :href="link.url || '#'"
                            preserve-scroll
                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm transition-colors"
                            :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border text-foreground hover:border-primary hover:text-primary'"
                        >
                            {{ link.label }}
                        </Link>
                        <button
                            type="button"
                            class="border-border text-foreground inline-flex h-10 w-10 items-center justify-center rounded-xl border disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!nextPage || isLoading"
                            @click="goToPage(nextPage)"
                        >
                            <ChevronRight class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>
                    <div v-if="isLoading" class="text-primary mt-5 flex items-center justify-center gap-2 text-sm">
                        <Loader2 class="h-4 w-4 animate-spin" aria-hidden="true" /> {{ t("labels.actions.loading") }}
                    </div>
                </section>
            </div>
        </PageShell>

        <div v-if="isFilterOpen" id="mobile-product-filters" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" :aria-label="t('labels.products.filter_and_sort')">
            <button type="button" class="absolute inset-0 bg-foreground/40" :aria-label="t('labels.actions.close')" @click="isFilterOpen = false"></button>
            <div class="absolute inset-x-0 bottom-0 max-h-[92vh] overflow-y-auto rounded-t-3xl bg-background px-5 pb-5 pt-4 shadow-2xl">
                <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-border"></div>
                <div class="mb-5 flex items-start justify-between border-b border-border pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-foreground">{{ t("labels.products.filter_and_sort") }}</h2>
                        <p class="text-muted-foreground mt-1 text-sm">{{ t("labels.products.filter_description") }}</p>
                    </div>
                    <button type="button" class="text-foreground rounded-full p-2" :aria-label="t('labels.actions.close')" @click="isFilterOpen = false">
                        <X class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
                <ProductFilters v-model="draftFilters" id-prefix="mobile-filter" :categories="categories" />
                <div class="mt-6 grid grid-cols-2 gap-3 border-t border-border pt-4">
                    <Button type="button" variant="outline" class="rounded-xl" @click="resetFilters">{{ t("labels.actions.reset_filters") }}</Button>
                    <Button type="button" class="bg-primary text-primary-foreground rounded-xl" @click="applyMobileFilters">
                        {{ t("labels.actions.apply_filters") }}
                    </Button>
                </div>
            </div>
        </div>
    </TemplateWrapper>
</template>
