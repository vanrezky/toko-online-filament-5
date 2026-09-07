<script setup>
import { computed, reactive } from "vue";
import { ChevronDown, ChevronUp, Star } from "lucide-vue-next";
import FormInput from "./FormInput.vue";
import FormRadio from "./FormRadio.vue";
import { cn, formatCompactNumber } from "../../lib/utils";
import { useI18n } from "vue-i18n";

const props = defineProps({
    modelValue: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    idPrefix: { type: String, default: "filter" },
});

const emit = defineEmits(["update:modelValue", "change"]);
const { t, locale } = useI18n();

const openSections = reactive({
    categories: true,
    price: true,
    rating: true,
    color: true,
    size: true,
    gender: true,
    promotions: true,
});

const priceRanges = computed(() => [
    { key: "under_100", min: "", max: 100000, label: t("labels.filters.price_under", { amount: formatPrice(100000) }) },
    { key: "100_500", min: 100000, max: 500000, label: t("labels.filters.price_between", { min: formatPrice(100000), max: formatPrice(500000) }) },
    { key: "500_1m", min: 500000, max: 1000000, label: t("labels.filters.price_between", { min: formatPrice(500000), max: formatPrice(1000000) }) },
    { key: "1m_5m", min: 1000000, max: 5000000, label: t("labels.filters.price_between", { min: formatPrice(1000000), max: formatPrice(5000000) }) },
    { key: "over_5m", min: 5000000, max: "", label: t("labels.filters.price_over", { amount: formatPrice(5000000) }) },
]);

const ratingOptions = [5, 4, 3, 2];
const variantEntries = [
    {
        key: "color",
        label: "Warna",
        options: ["Hitam", "Putih", "Merah", "Biru", "Hijau", "Kuning", "Orange", "Ungu", "Cokelat", "Abu Abu"],
    },
    {
        key: "size",
        label: "Ukuran",
        options: ["S", "M", "L", "XL", "XXL"],
    },
    {
        key: "gender",
        label: "Gender",
        options: ["Pria", "Wanita", "Unisex"],
    },
].map((filter) => ({
    ...filter,
    options: filter.options.map((name) => ({ id: `${filter.key}-${name}`, name })),
}));

const formatPrice = (amount) => "Rp" + formatCompactNumber(amount, locale.value).replace(/\+$/, "");
const selected = (field, value) => (props.modelValue[field] || []).includes(value);
const selectedSingle = (field) => props.modelValue[field]?.[0] || "";
const toggleSection = (section) => {
    openSections[section] = !openSections[section];
};

const updateField = (field, value) => {
    emit("update:modelValue", { ...props.modelValue, [field]: value });
    emit("change");
};

const selectRadio = (field, value) => {
    updateField(field, value ? [value] : []);
};

const selectPriceRange = (range) => {
    emit("update:modelValue", {
        ...props.modelValue,
        price_min: range.min,
        price_max: range.max,
    });
    emit("change");
};

const selectedPriceRange = computed(() => priceRanges.value.find(
    (range) => String(range.min) === String(props.modelValue.price_min || "") && String(range.max) === String(props.modelValue.price_max || ""),
)?.key || "");

const variantLabel = (filter) => {
    const key = `labels.filters.variant_${filter.key}`;
    const translated = t(key);

    return translated === key ? filter.label : translated;
};

const colorSwatchClass = (name) => {
    const swatches = {
        "abu abu": "bg-gray-500",
        biru: "bg-blue-600",
        cokelat: "bg-amber-700",
        hijau: "bg-green-600",
        hitam: "bg-black",
        kuning: "bg-yellow-500",
        merah: "bg-red-600",
        orange: "bg-orange-500",
        putih: "bg-white",
        ungu: "bg-purple-500",
    };

    return cn("h-6 w-6 rounded-full border border-border", swatches[String(name).toLowerCase()] || "bg-secondary");
};

const variantOptionClass = (filter, option) => cn(
    "inline-flex cursor-pointer items-center rounded-full border border-border text-xs transition-colors hover:border-primary hover:text-primary",
    filter.key === "color" ? "h-10 w-10 justify-center p-1.5" : "gap-1.5 px-3 py-1.5",
    selectedSingle("variant_" + filter.key) === option.name ? "border-primary bg-primary text-primary-foreground" : "text-muted-foreground",
);
</script>

<template>
    <div class="space-y-6">
        <section>
            <button
                type="button"
                class="mb-3 flex w-full items-center justify-between text-left"
                :aria-expanded="openSections.categories"
                aria-controls="filter-section-categories"
                @click="toggleSection('categories')"
            >
                <h3 class="text-sm font-bold text-foreground">{{ t("labels.form.category") }}</h3>
                <ChevronUp v-if="openSections.categories" class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                <ChevronDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
            </button>
            <div v-if="openSections.categories" id="filter-section-categories" class="space-y-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <FormRadio
                        :name="`${idPrefix}-category`"
                        :checked="(modelValue.categories || []).length === 0"
                        @change="selectRadio('categories', '')"
                    />
                    <span>{{ t("labels.filters.all_categories") }}</span>
                </label>
                <label v-for="category in categories" :key="category.id" class="flex cursor-pointer items-center gap-2 text-sm">
                    <FormRadio
                        :name="`${idPrefix}-category`"
                        :checked="selected('categories', category.slug)"
                        @change="selectRadio('categories', category.slug)"
                    />
                    <span>{{ category.name }}</span>
                </label>
            </div>
        </section>

        <section class="border-t border-border pt-5">
            <button
                type="button"
                class="mb-3 flex w-full items-center justify-between text-left"
                :aria-expanded="openSections.price"
                aria-controls="filter-section-price"
                @click="toggleSection('price')"
            >
                <h3 class="text-sm font-bold text-foreground">{{ t("labels.form.price") }}</h3>
                <ChevronUp v-if="openSections.price" class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                <ChevronDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
            </button>
            <div v-if="openSections.price" id="filter-section-price">
                <div class="flex items-center gap-2">
                    <FormInput
                        :model-value="modelValue.price_min"
                        type="number"
                        min="0"
                        :placeholder="t('placeholders.price_min')"
                        class="py-2"
                        @update:model-value="updateField('price_min', $event)"
                    />
                    <span class="text-muted-foreground">-</span>
                    <FormInput
                        :model-value="modelValue.price_max"
                        type="number"
                        min="0"
                        :placeholder="t('placeholders.price_max')"
                        class="py-2"
                        @update:model-value="updateField('price_max', $event)"
                    />
                </div>
                <div class="mt-3 space-y-2">
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                        <FormRadio :name="`${idPrefix}-price-range`" :checked="!selectedPriceRange" @change="selectPriceRange({ min: '', max: '' })" />
                        <span>{{ t("labels.filters.all") }}</span>
                    </label>
                    <label v-for="range in priceRanges" :key="range.key" class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                        <FormRadio :name="`${idPrefix}-price-range`" :checked="selectedPriceRange === range.key" @change="selectPriceRange(range)" />
                        <span>{{ range.label }}</span>
                    </label>
                </div>
            </div>
        </section>

        <section class="border-t border-border pt-5">
            <button
                type="button"
                class="mb-3 flex w-full items-center justify-between text-left"
                :aria-expanded="openSections.rating"
                aria-controls="filter-section-rating"
                @click="toggleSection('rating')"
            >
                <h3 class="text-sm font-bold text-foreground">{{ t("labels.filters.rating_minimum") }}</h3>
                <ChevronUp v-if="openSections.rating" class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                <ChevronDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
            </button>
            <div v-if="openSections.rating" id="filter-section-rating" class="space-y-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <FormRadio :name="`${idPrefix}-rating`" :checked="!modelValue.rating_min" @change="updateField('rating_min', '')" />
                    <span>{{ t("labels.filters.all") }}</span>
                </label>
                <label v-for="rating in ratingOptions" :key="rating" class="flex cursor-pointer items-center gap-2 text-sm">
                    <FormRadio :name="`${idPrefix}-rating`" :checked="Number(modelValue.rating_min) === rating" @change="updateField('rating_min', rating)" />
                    <span class="inline-flex items-center gap-0.5" :aria-label="t('labels.filters.rating_chip', { rating })">
                        <Star v-for="star in rating" :key="star" class="h-3.5 w-3.5 fill-current text-primary" aria-hidden="true" />
                    </span>
                </label>
            </div>
        </section>

        <section v-for="filter in variantEntries" :key="filter.key" class="border-t border-border pt-5">
            <button
                type="button"
                class="mb-3 flex w-full items-center justify-between text-left"
                :aria-expanded="openSections[filter.key]"
                :aria-controls="`filter-section-${filter.key}`"
                @click="toggleSection(filter.key)"
            >
                <h3 class="text-sm font-bold text-foreground">{{ variantLabel(filter) }}</h3>
                <ChevronUp v-if="openSections[filter.key]" class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                <ChevronDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
            </button>
            <div v-if="openSections[filter.key]" :id="`filter-section-${filter.key}`" class="flex flex-wrap gap-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm" :title="t('labels.filters.all')">
                    <FormRadio
                        :name="`${idPrefix}-variant-${filter.key}`"
                        :checked="!selectedSingle('variant_' + filter.key)"
                        @change="selectRadio('variant_' + filter.key, '')"
                    />
                    <span>{{ t("labels.filters.all") }}</span>
                </label>
                <label
                    v-for="option in filter.options"
                    :key="option.id"
                    :class="variantOptionClass(filter, option)"
                    :title="option.name"
                >
                    <FormRadio
                        :name="`${idPrefix}-variant-${filter.key}`"
                        :checked="selectedSingle('variant_' + filter.key) === option.name"
                        class="sr-only"
                        :aria-label="option.name"
                        @change="selectRadio('variant_' + filter.key, option.name)"
                    />
                    <span v-if="filter.key === 'color'" :class="colorSwatchClass(option.name)" aria-hidden="true"></span>
                    <span :class="filter.key === 'color' ? 'sr-only' : ''">{{ option.name }}</span>
                </label>
            </div>
        </section>

        <section class="border-t border-border pt-5">
            <button
                type="button"
                class="mb-3 flex w-full items-center justify-between text-left"
                :aria-expanded="openSections.promotions"
                aria-controls="filter-section-promotions"
                @click="toggleSection('promotions')"
            >
                <h3 class="text-sm font-bold text-foreground">{{ t("labels.filters.promotions") }}</h3>
                <ChevronUp v-if="openSections.promotions" class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                <ChevronDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
            </button>
            <div v-if="openSections.promotions" id="filter-section-promotions" class="space-y-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <FormRadio :name="`${idPrefix}-promotion`" :checked="!modelValue.promos?.length" @change="selectRadio('promos', '')" />
                    <span>{{ t("labels.filters.all") }}</span>
                </label>
                <label v-for="promo in ['discount', 'new', 'flash_sale']" :key="promo" class="flex cursor-pointer items-center gap-2 text-sm">
                    <FormRadio :name="`${idPrefix}-promotion`" :checked="selected('promos', promo)" @change="selectRadio('promos', promo)" />
                    <span>{{ t("labels.filters.promo_" + promo) }}</span>
                </label>
            </div>
        </section>
    </div>
</template>
