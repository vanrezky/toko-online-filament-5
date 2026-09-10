<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Deferred, Link, router, usePage } from "@inertiajs/vue3";
import { cn, formatCompactNumber, formatCurrency } from "../../lib/utils";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import QuantityStepper from "../../components/UI/QuantityStepper.vue";
import ProductRecommendationCard from "../../components/UI/ProductRecommendationCard.vue";
import Button from "@frontend/components/UI/Button.vue";
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    FileText,
    Heart,
    ImageOff,
    List,
    MapPin,
    Minus,
    Plus,
    Scale,
    ShoppingBag,
    Star,
    Tag,
    X,
    ZoomIn,
} from "lucide-vue-next";

const props = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: [Array, Object], default: () => [] },
});

const page = usePage();
const { t, locale } = useI18n();
const selectedImageIndex = ref(0);
const isImageZoomOpen = ref(false);
const zoomDialog = ref(null);
const imageZoomTrigger = ref(null);
const galleryThumbnails = ref(null);
const thumbnailElements = ref([]);
const visibleThumbnailIndexes = ref(new Set([0]));
const quantity = ref(props.product.min_order || 1);
const selectedAttributes = ref({});
const activeFaq = ref(null);
const activeDesktopTab = ref("description");
const activeMobileDetail = ref("description");
const isDescriptionExpanded = ref(false);
const reviews = ref([]);
const ratingData = ref(null);
const ratingSection = ref(null);
const reviewsTabPanel = ref(null);
const reviewPage = ref(0);
const isLoadingReviews = ref(false);
const hasMoreReviews = ref(true);
const selectedReviewRating = ref("all");
const reviewsError = ref(false);

const formatProductCount = (count) => formatCompactNumber(count, locale.value);
const localeCode = computed(() => (locale.value === "id" ? "id-ID" : "en-US"));
const galleryMedia = computed(() => {
    const originals = Array.isArray(props.product.images) ? props.product.images.filter(Boolean) : [];
    const thumbnails = Array.isArray(props.product.image_thumbnails) ? props.product.image_thumbnails : [];
    const media = originals.map((url, index) => ({
        url,
        thumbnail: thumbnails[index] || url,
    }));

    if (media.length) return media;

    return props.product.thumbnail ? [{ url: props.product.thumbnail, thumbnail: props.product.thumbnail }] : [];
});
const selectedImage = computed(() => galleryMedia.value[selectedImageIndex.value]?.url || null);
const selectedImageNumber = computed(() => (selectedImage.value ? selectedImageIndex.value + 1 : 0));
const relatedItems = computed(() => {
    if (Array.isArray(props.relatedProducts)) return props.relatedProducts;

    return props.relatedProducts?.data || [];
});

watch(
    galleryMedia,
    (media) => {
        if (!media.length) {
            selectedImageIndex.value = 0;
            return;
        }

        if (selectedImageIndex.value >= media.length) selectedImageIndex.value = 0;
    },
    { immediate: true },
);

const selectGalleryImage = (index) => {
    selectedImageIndex.value = index;
};

const cycleGalleryImage = (direction) => {
    if (galleryMedia.value.length < 2) return;

    selectedImageIndex.value =
        (selectedImageIndex.value + direction + galleryMedia.value.length) % galleryMedia.value.length;
};

const openImageZoom = async () => {
    if (!selectedImage.value) return;

    isImageZoomOpen.value = true;
    await nextTick();
    zoomDialog.value?.focus();
};

const closeImageZoom = async () => {
    isImageZoomOpen.value = false;
    await nextTick();
    imageZoomTrigger.value?.$el?.focus();
};

const isWishlisted = computed(() => page.props.wishlist_product_ids?.includes(props.product.id));

const toggleWishlist = () => {
    router.post(
        route("frontend.wishlist.toggle"),
        { product_id: props.product.id },
        { preserveScroll: true },
    );
};

const hasVariants = computed(() => props.product.variants && props.product.variants.length > 0);
const attributeGroups = computed(() => {
    if (!hasVariants.value) return {};

    const groups = {};
    props.product.variants.forEach((variant) => {
        variant.attributes.forEach((attr) => {
            if (!groups[attr.name]) groups[attr.name] = new Set();
            groups[attr.name].add(attr.option);
        });
    });

    Object.keys(groups).forEach((key) => {
        groups[key] = Array.from(groups[key]);
    });

    return groups;
});
const requiredAttributes = computed(() => Object.keys(attributeGroups.value));
const selectedVariant = computed(() => {
    if (!hasVariants.value) return null;

    const selectedKeys = Object.keys(selectedAttributes.value);
    if (selectedKeys.length !== Object.keys(attributeGroups.value).length) return null;

    return props.product.variants.find((variant) =>
        variant.attributes.every((attr) => selectedAttributes.value[attr.name] === attr.option),
    );
});
const optionClasses = (name, option) =>
    cn(
        "min-h-10 rounded-lg border px-3.5 py-2 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-primary/30",
        selectedAttributes.value[name] === option
            ? "border-primary bg-primary/10 text-primary shadow-sm"
            : "border-border bg-white text-foreground hover:border-primary/50 hover:bg-primary/5",
    );

const activeFlashsale = computed(() => props.product.pricing?.flashsale ?? null);
const displayPrice = computed(() => {
    let basePrice = parseFloat(props.product.pricing?.final_price ?? props.product.sale_price ?? props.product.price);

    if (selectedVariant.value) {
        basePrice = parseFloat(selectedVariant.value.price);
        if (activeFlashsale.value) {
            basePrice *= 1 - parseFloat(activeFlashsale.value.discount_percentage) / 100;
        }
    }

    if (activeFlashsale.value) return basePrice;

    if (props.product.wholesales && props.product.wholesales.length > 0) {
        const minOrder = props.product.min_order || 1;
        const applicableWholesale = [...props.product.wholesales]
            .filter((wholesale) => wholesale.min_qty > minOrder)
            .sort((a, b) => b.min_qty - a.min_qty)
            .find((wholesale) => quantity.value >= wholesale.min_qty);

        if (applicableWholesale) return parseFloat(applicableWholesale.price);
    }

    return basePrice;
});
const displayOriginalPrice = computed(() =>
    selectedVariant.value
        ? parseFloat(selectedVariant.value.price)
        : parseFloat(props.product.pricing?.original_price ?? props.product.price),
);
const activeWholesale = computed(() => {
    if (activeFlashsale.value || !props.product.wholesales?.length) return null;

    const minOrder = props.product.min_order || 1;
    return [...props.product.wholesales]
        .filter((wholesale) => wholesale.min_qty > minOrder)
        .sort((a, b) => b.min_qty - a.min_qty)
        .find((wholesale) => quantity.value >= wholesale.min_qty);
});
const nextWholesale = computed(() => {
    if (activeFlashsale.value || !props.product.wholesales?.length) return null;

    return [...props.product.wholesales]
        .sort((a, b) => a.min_qty - b.min_qty)
        .find((wholesale) => quantity.value < wholesale.min_qty);
});
const displayStock = computed(() => {
    const productStock = selectedVariant.value ? selectedVariant.value.stock : props.product.stock;

    return activeFlashsale.value ? Math.min(productStock, activeFlashsale.value.stock) : productStock;
});
const isSale = computed(() => ["sale", "flashsale"].includes(props.product.pricing?.source));
const hasPositiveRatingSummary = computed(
    () => ratingData.value?.summary.count > 0 && ratingData.value.summary.average >= 4,
);

const specRows = computed(() => [
    { label: t("labels.product.spec_code"), value: props.product.code || "—" },
    { label: t("labels.product.spec_category"), value: props.product.category?.name || "—" },
    { label: t("labels.product.spec_weight"), value: ((props.product.weight || 0) / 1000).toFixed(2) + " kg" },
    { label: t("labels.product.spec_warehouse"), value: props.product.warehouse?.name || t("labels.product.default_warehouse") },
]);
const mobileDetailSections = computed(() => [
    { key: "description", label: t("labels.product.description"), icon: FileText },
    { key: "specifications", label: t("labels.product.specifications"), icon: List },
    {
        key: "reviews",
        label: ratingData.value
            ? t("labels.product.reviews_heading") + " (" + formatProductCount(ratingData.value.summary.count) + ")"
            : t("labels.product.reviews_heading"),
        icon: Star,
    },
]);

const seoTitle = computed(() => props.product.meta?.title || props.product.name);
const seoDescription = computed(() => props.product.meta?.description || props.product.description?.substring(0, 160));
const seoKeywords = computed(() => props.product.meta?.keyword);

const updateQuantity = (nextQuantity) => {
    const minOrder = props.product.min_order || 1;
    const maxStock = Math.max(displayStock.value, minOrder);
    const value = Number(nextQuantity);

    if (Number.isFinite(value)) quantity.value = Math.min(Math.max(value, minOrder), maxStock);
};

const selectAttribute = (name, option) => {
    selectedAttributes.value[name] = option;
    quantity.value = props.product.min_order || 1;
};

const toggleFaq = (index) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const toggleMobileDetail = (key) => {
    activeMobileDetail.value = activeMobileDetail.value === key ? null : key;

    if (key === "reviews" && activeMobileDetail.value === key && !ratingData.value) fetchReviews(1, true);
};

const openReviews = async () => {
    activeDesktopTab.value = "reviews";
    if (!ratingData.value && !isLoadingReviews.value) fetchReviews(1, true);
    await nextTick();
    reviewsTabPanel.value?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const ratingPercent = (rating) => {
    const total = ratingData.value?.summary.count || 0;

    return total ? ((ratingData.value.summary.distribution?.[rating] || 0) / total) * 100 : 0;
};

const fetchReviews = async (pageNumber = 1, replace = false) => {
    if (isLoadingReviews.value || (!hasMoreReviews.value && !replace)) return;

    isLoadingReviews.value = true;
    reviewsError.value = false;
    try {
        const response = await axios.get(route("frontend.products.reviews", props.product.slug), {
            params: {
                page: pageNumber,
                ...(selectedReviewRating.value !== "all" ? { rating: selectedReviewRating.value } : {}),
            },
        });
        ratingData.value = response.data.data;
        reviews.value = replace ? response.data.data.reviews : [...reviews.value, ...response.data.data.reviews];
        reviewPage.value = response.data.meta.current_page;
        hasMoreReviews.value = response.data.meta.current_page < response.data.meta.last_page;
    } catch {
        reviewsError.value = true;
    } finally {
        isLoadingReviews.value = false;
    }
};

const loadMoreReviews = () => fetchReviews(reviewPage.value + 1);

const filterReviews = (rating) => {
    if (selectedReviewRating.value === rating) return;

    selectedReviewRating.value = rating;
    reviewPage.value = 0;
    hasMoreReviews.value = true;
    fetchReviews(1, true);
};

let ratingObserver;
let thumbnailObserver;
onMounted(() => {
    ratingObserver = new IntersectionObserver(
        ([entry]) => {
            if (!entry.isIntersecting || ratingData.value) return;
            fetchReviews(1, true);
            ratingObserver.disconnect();
        },
        { rootMargin: "250px 0px" },
    );

    if (ratingSection.value) ratingObserver.observe(ratingSection.value);

    thumbnailObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                visibleThumbnailIndexes.value = new Set([
                    ...visibleThumbnailIndexes.value,
                    Number(entry.target.dataset.index),
                ]);
                thumbnailObserver.unobserve(entry.target);
            });
        },
        { root: galleryThumbnails.value, rootMargin: "120px 0px" },
    );

    thumbnailElements.value.forEach((element) => thumbnailObserver.observe(element));
});

const setThumbnailElement = (element, index) => {
    if (element) thumbnailElements.value[index] = element;
};

onBeforeUnmount(() => {
    ratingObserver?.disconnect();
    thumbnailObserver?.disconnect();
});

const addToCart = () => {
    router.post(
        route("frontend.cart.store"),
        {
            product_id: props.product.id,
            product_variant_id: selectedVariant.value?.id,
            quantity: quantity.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                if (errors?.redirect) {
                    window.location.href = errors.redirect;
                    return;
                }

                const firstError = errors?.quantity ?? Object.values(errors ?? {})[0];
                const message = Array.isArray(firstError) ? firstError[0] : firstError;

                toast.error(message || t("messages.error.generic"));
            },
        },
    );
};

const buyNow = async () => {
    try {
        const response = await axios.post(route("frontend.cart.store"), {
            product_id: props.product.id,
            product_variant_id: selectedVariant.value?.id,
            quantity: quantity.value,
        });

        router.visit(route("frontend.checkout", { cart_item_ids: [response.data.cart_item_id] }));
    } catch (error) {
        console.error("Failed to add Buy Now item to cart", error);
    }
};
</script>

<template>
    <TemplateWrapper
        :shell="false"
        :title="seoTitle"
        :description="seoDescription"
        :keywords="seoKeywords"
        :social-image="product.thumbnail"
    >
        <PageShell container class="overflow-x-clip pb-[calc(6rem+env(safe-area-inset-bottom))] sm:pb-0">
            <nav class="text-muted-foreground mb-5 hidden items-center gap-2 text-xs md:mb-7 md:flex" :aria-label="t('labels.product.breadcrumb')">
                <Link :href="route('frontend.home')" class="rounded-sm transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                    {{ t("labels.breadcrumb.home") }}
                </Link>
                <ChevronRight class="h-3.5 w-3.5" aria-hidden="true" />
                <Link :href="route('frontend.products')" class="rounded-sm transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30">
                    {{ t("labels.breadcrumb.products") }}
                </Link>
                <template v-if="product.category">
                    <ChevronRight class="h-3.5 w-3.5" aria-hidden="true" />
                    <Link
                        :href="route('frontend.products', { category: product.category.slug })"
                        class="rounded-sm transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                    >
                        {{ product.category.name }}
                    </Link>
                </template>
                <ChevronRight class="h-3.5 w-3.5" aria-hidden="true" />
                <span class="max-w-[18rem] truncate text-foreground">{{ product.name }}</span>
            </nav>

            <div
                class="product-detail-grid grid min-w-0 gap-6 xl:gap-8"
                :class="{ 'product-detail-grid--single-image': galleryMedia.length === 1 }"
            >
                <section class="min-w-0" :aria-label="t('labels.product.gallery')">
                    <div class="flex min-w-0 items-start gap-3 md:gap-4">
                        <div
                            v-if="galleryMedia.length > 1"
                            ref="galleryThumbnails"
                            class="flex max-h-[32rem] w-16 shrink-0 flex-col gap-2 overflow-y-auto pb-1 [scrollbar-width:none] md:w-[4.5rem] md:gap-3 [&::-webkit-scrollbar]:hidden"
                        >
                            <Button
                                v-for="(media, index) in galleryMedia"
                                :key="media.url + '-' + index"
                                :ref="(element) => setThumbnailElement(element?.$el || element, index)"
                                :data-index="index"
                                type="button"
                                :aria-label="t('labels.product.view_image', { number: index + 1 })"
                                :aria-pressed="selectedImageIndex === index"
                                :class="cn(
                                    'h-16 w-16 shrink-0 overflow-hidden rounded-xl border bg-white p-0 shadow-sm transition-all focus-visible:ring-2 focus-visible:ring-primary/40 md:h-[4.5rem] md:w-[4.5rem]',
                                    selectedImageIndex === index
                                        ? 'border-primary ring-1 ring-primary/30'
                                        : 'border-border opacity-65 hover:border-primary/50 hover:opacity-100',
                                )"
                                @click="selectGalleryImage(index)"
                            >
                                <img
                                    v-if="visibleThumbnailIndexes.has(index)"
                                    :src="media.thumbnail"
                                    :alt="product.name + ' ' + (index + 1)"
                                    loading="lazy"
                                    decoding="async"
                                    fetchpriority="low"
                                    class="h-full w-full object-cover"
                                />
                            </Button>
                        </div>

                        <div class="relative min-w-0 flex-1">
                            <Button
                                v-if="selectedImage"
                                ref="imageZoomTrigger"
                                type="button"
                                :aria-label="t('labels.product.view_image', { number: selectedImageNumber })"
                                aria-haspopup="dialog"
                                class="group relative block aspect-square w-full overflow-hidden rounded-2xl border border-border bg-secondary/20 p-0 shadow-sm transition-shadow hover:shadow-lg focus-visible:ring-offset-4"
                                @click="openImageZoom"
                            >
                                <img
                                    :src="selectedImage"
                                    :alt="product.name"
                                    fetchpriority="high"
                                    decoding="async"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    class="pointer-events-none absolute right-4 bottom-4 hidden h-10 w-10 items-center justify-center rounded-full bg-white/95 text-foreground opacity-0 shadow-sm transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100 sm:flex"
                                    aria-hidden="true"
                                >
                                    <ZoomIn class="h-5 w-5" />
                                </span>
                            </Button>
                            <div v-else class="flex aspect-square w-full flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-secondary/20 text-center">
                                <ImageOff class="h-10 w-10 text-muted-foreground" aria-hidden="true" />
                                <p class="mt-3 text-sm font-medium text-muted-foreground">{{ t("labels.product.no_image") }}</p>
                            </div>

                            <template v-if="galleryMedia.length > 1">
                                <Button
                                    type="button"
                                    :aria-label="t('labels.carousel.previous')"
                                    class="absolute top-1/2 left-2 flex h-10 w-10 -translate-y-1/2 justify-center rounded-full border-0 bg-white/95 p-0 text-foreground shadow-md hover:bg-white focus-visible:ring-2 focus-visible:ring-primary/40 md:left-4"
                                    @click.stop="cycleGalleryImage(-1)"
                                >
                                    <ChevronLeft class="h-5 w-5" aria-hidden="true" />
                                </Button>
                                <Button
                                    type="button"
                                    :aria-label="t('labels.carousel.next')"
                                    class="absolute top-1/2 right-2 flex h-10 w-10 -translate-y-1/2 justify-center rounded-full border-0 bg-white/95 p-0 text-foreground shadow-md hover:bg-white focus-visible:ring-2 focus-visible:ring-primary/40 md:right-4"
                                    @click.stop="cycleGalleryImage(1)"
                                >
                                    <ChevronRight class="h-5 w-5" aria-hidden="true" />
                                </Button>
                                <span class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-foreground/75 px-2.5 py-1 text-[11px] font-semibold text-white md:hidden">
                                    {{ selectedImageNumber }} / {{ galleryMedia.length }}
                                </span>
                            </template>
                        </div>
                    </div>
                </section>

                <section class="min-w-0 space-y-4 lg:space-y-5" :aria-label="t('labels.product.purchase_information')">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span v-if="product.category" class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                                <Tag class="h-3.5 w-3.5" aria-hidden="true" />
                                {{ product.category.name }}
                            </span>
                            <span v-else></span>
                            <Button
                                type="button"
                                :aria-label="isWishlisted ? t('labels.product.saved_to_wishlist') : t('labels.product.save_to_wishlist')"
                                :aria-pressed="isWishlisted"
                                class="h-11 w-11 rounded-full border border-border bg-white p-0 text-foreground hover:border-primary hover:text-primary focus-visible:ring-2 focus-visible:ring-primary/30"
                                @click="toggleWishlist"
                            >
                                <Heart class="h-5 w-5" :class="isWishlisted ? 'fill-primary text-primary' : ''" aria-hidden="true" />
                            </Button>
                        </div>

                        <div>
                            <h1 class="text-foreground text-xl font-bold leading-tight tracking-[-0.025em] md:text-2xl">{{ product.name }}</h1>
                            <div v-if="product.description" class="prose prose-sm mt-2 max-w-none line-clamp-3 text-muted-foreground leading-relaxed" v-html="product.description"></div>
                        </div>

                        <div v-if="ratingData" class="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                            <span class="flex items-center gap-1 font-semibold text-foreground">
                                <Star class="h-4 w-4 fill-primary text-primary" aria-hidden="true" />
                                {{ ratingData.summary.average.toFixed(1) }}
                            </span>
                            <span>({{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }})</span>
                            <span class="text-border">|</span>
                            <span>{{ formatProductCount(product.sold_count) }} {{ t("labels.product.sold") }}</span>
                            <span class="text-border">|</span>
                            <span class="flex items-center gap-1.5">
                                <Scale class="h-3.5 w-3.5 text-primary" aria-hidden="true" />
                                {{ t("labels.product.weight_unit", { weight: ((product.weight || 0) / 1000).toFixed(2) }) }}
                            </span>
                        </div>
                        <div v-else class="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                            <span>{{ formatProductCount(product.sold_count) }} {{ t("labels.product.sold") }}</span>
                            <span class="text-border">|</span>
                            <span class="flex items-center gap-1.5">
                                <Scale class="h-3.5 w-3.5 text-primary" aria-hidden="true" />
                                {{ t("labels.product.weight_unit", { weight: ((product.weight || 0) / 1000).toFixed(2) }) }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="text-3xl font-bold tracking-[-0.03em] text-primary md:text-4xl">{{ formatCurrency(displayPrice, localeCode) }}</span>
                            <span v-if="isSale && !activeWholesale" class="text-base text-muted-foreground line-through">{{ formatCurrency(displayOriginalPrice, localeCode) }}</span>
                            <span v-if="activeFlashsale || product.discount_percentage" class="rounded-full bg-destructive px-2.5 py-1 text-xs font-bold text-white">
                                {{ t("labels.product.discount_off", { discount: activeFlashsale?.discount_percentage ?? product.discount_percentage }) }}
                            </span>
                        </div>

                        <p v-if="activeWholesale" class="rounded-xl bg-green-50 px-3.5 py-3 text-sm font-medium text-green-700">
                            {{ t("labels.product.wholesale_active", { qty: activeWholesale.min_qty, price: formatCurrency(activeWholesale.price, localeCode) }) }}
                        </p>
                        <p v-else-if="nextWholesale" class="rounded-xl bg-secondary px-3.5 py-3 text-sm text-muted-foreground">
                            {{ t("labels.product.wholesale_next", { qty: nextWholesale.min_qty, price: formatCurrency(nextWholesale.price, localeCode) }) }}
                        </p>
                    </div>

                    <div v-if="hasVariants" class="space-y-4 border-t border-border pt-4">
                        <div v-for="(options, name) in attributeGroups" :key="name" class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <label class="text-sm font-semibold text-foreground">{{ name }}</label>
                                <span v-if="selectedAttributes[name]" class="text-sm text-muted-foreground">{{ selectedAttributes[name] }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <Button
                                    v-for="option in options"
                                    :key="option"
                                    type="button"
                                    :aria-pressed="selectedAttributes[name] === option"
                                    :class="optionClasses(name, option)"
                                    @click="selectAttribute(name, option)"
                                >
                                    {{ option }}
                                </Button>
                            </div>
                        </div>
                        <p v-if="!selectedVariant" id="product-option-guidance" class="text-sm text-muted-foreground" aria-live="polite">
                            {{ t("labels.product.select_options_hint", { options: requiredAttributes.join(", ") }) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
                        <div>
                            <span class="text-sm font-semibold text-foreground">{{ t("labels.product.quantity") }}</span>
                            <p :class="displayStock > 0 ? 'text-green-600' : 'text-destructive'" class="mt-1 text-sm">
                                {{ displayStock > 0 ? t("labels.product.stock", { stock: displayStock }) : t("labels.product.out_of_stock") }}
                            </p>
                        </div>
                        <QuantityStepper
                            :model-value="quantity"
                            :min="product.min_order || 1"
                            :max="displayStock"
                            :disabled="displayStock <= 0"
                            :decrease-label="t('labels.product.decrease_quantity')"
                            :increase-label="t('labels.product.increase_quantity')"
                            :quantity-label="t('labels.product.quantity')"
                            class="w-24 shrink-0"
                            @update:model-value="updateQuantity"
                        />
                    </div>
                    <p v-if="product.min_order && product.min_order > 1" class="-mt-2 text-xs text-muted-foreground">
                        {{ t("labels.product.min_order", { min: product.min_order }) }}
                    </p>

                    <div class="hidden gap-3 md:grid md:grid-cols-2">
                        <Button
                            type="button"
                            size="sm"
                            :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)"
                            class="min-h-11 whitespace-nowrap border-primary bg-white text-primary hover:bg-primary/5 focus-visible:ring-2 focus-visible:ring-primary/30"
                            @click="addToCart"
                        >
                            <ShoppingBag class="h-5 w-5" aria-hidden="true" />
                            {{ hasVariants && !selectedVariant ? t("labels.actions.select_options") : t("labels.actions.add_to_cart") }}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)"
                            class="min-h-11 whitespace-nowrap border-primary bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-primary/40"
                            @click="buyNow"
                        >
                            {{ t("labels.product.buy_now") }}
                        </Button>
                    </div>
                </section>

            </div>

            <div class="product-detail-lower mt-6 grid min-w-0 gap-6">
                <section class="min-w-0 overflow-hidden rounded-3xl border border-border bg-white" :aria-label="t('labels.product.details_navigation')">
                    <div class="hidden border-b border-border md:flex md:items-center md:gap-7 md:px-6">
                        <Button
                            v-for="tab in [
                                { key: 'description', label: t('labels.product.description') },
                                { key: 'specifications', label: t('labels.product.specifications') },
                                { key: 'reviews', label: t('labels.product.reviews_heading') },
                            ]"
                            :key="tab.key"
                            type="button"
                            :aria-selected="activeDesktopTab === tab.key"
                            role="tab"
                            :class="cn(
                                'relative min-h-14 rounded-none border-0 border-b bg-transparent px-0 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-primary/30',
                                activeDesktopTab === tab.key ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
                            )"
                            @click="tab.key === 'reviews' ? openReviews() : (activeDesktopTab = tab.key)"
                        >
                            {{ tab.label }}
                            <span v-if="tab.key === 'reviews' && ratingData" class="font-normal">({{ formatProductCount(ratingData.summary.count) }})</span>
                        </Button>
                    </div>

                    <div class="hidden p-5 md:block md:p-6">
                        <div v-if="activeDesktopTab === 'description'" class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(15rem,.75fr)]">
                            <div>
                                <div v-if="product.description" class="prose prose-sm max-w-none text-sm leading-6 text-muted-foreground" :class="isDescriptionExpanded ? '' : 'max-h-28 overflow-hidden'" v-html="product.description"></div>
                                <p v-else class="text-sm leading-6 text-muted-foreground">{{ t("labels.product.description_unavailable") }}</p>
                                <Button
                                    v-if="product.description"
                                    type="button"
                                    class="mt-3 border-0 bg-transparent px-0 text-sm font-semibold text-primary hover:bg-transparent hover:text-primary/80"
                                    @click="isDescriptionExpanded = !isDescriptionExpanded"
                                >
                                    <ChevronUp v-if="isDescriptionExpanded" class="h-4 w-4" aria-hidden="true" />
                                    <ChevronDown v-else class="h-4 w-4" aria-hidden="true" />
                                    {{ isDescriptionExpanded ? t("labels.product.show_less") : t("labels.product.read_more") }}
                                </Button>
                            </div>
                            <dl class="grid content-start grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 rounded-2xl bg-secondary/45 p-4 text-sm">
                                <template v-for="spec in specRows" :key="spec.label">
                                    <dt class="text-muted-foreground">{{ spec.label }}</dt>
                                    <dd class="min-w-0 font-medium text-foreground">{{ spec.value }}</dd>
                                </template>
                            </dl>
                        </div>
                        <dl v-else-if="activeDesktopTab === 'specifications'" class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            <div v-for="spec in specRows" :key="spec.label" class="flex items-center justify-between gap-4 border-b border-border pb-3 text-sm">
                                <dt class="text-muted-foreground">{{ spec.label }}</dt>
                                <dd class="text-right font-medium text-foreground">{{ spec.value }}</dd>
                            </div>
                        </dl>
                        <div v-else ref="reviewsTabPanel" class="space-y-5">
                            <div v-if="!ratingData" class="flex min-h-40 items-center justify-center text-center" aria-live="polite">
                                <div v-if="reviewsError" class="flex flex-col items-center gap-4">
                                    <p class="text-sm text-muted-foreground">{{ t("labels.product.reviews_load_failed") }}</p>
                                    <Button type="button" class="border-primary bg-white text-primary hover:bg-primary/5" @click="fetchReviews(1, true)">{{ t("labels.actions.retry") }}</Button>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">{{ t("labels.product.loading_reviews") }}</p>
                            </div>
                            <template v-else>
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-lg font-bold text-foreground">{{ t("labels.product.reviews_heading") }}</h3>
                                    <span class="text-sm text-muted-foreground">{{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }}</span>
                                </div>
                                <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                    <Button
                                        v-for="rating in ['all', 5, 4, 3, 2, 1]"
                                        :key="rating"
                                        type="button"
                                        :aria-pressed="selectedReviewRating === rating"
                                        :class="cn(
                                            'shrink-0 rounded-full px-3 py-1.5 text-xs focus-visible:ring-2 focus-visible:ring-primary/30',
                                            selectedReviewRating === rating ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-white text-muted-foreground hover:border-primary hover:text-primary',
                                        )"
                                        @click="filterReviews(rating)"
                                    >
                                        <template v-if="rating === 'all'">{{ t("labels.product.all") }} ({{ formatProductCount(ratingData.summary.count) }})</template>
                                        <template v-else>{{ t("labels.product.stars", { rating }) }} ({{ formatProductCount(ratingData.summary.distribution?.[rating] || 0) }})</template>
                                    </Button>
                                </div>
                                <div class="space-y-3">
                                    <article v-for="review in reviews.slice(0, 3)" :key="review.id" class="rounded-xl border border-border p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">{{ review.reviewer_name }}</p>
                                                <p class="mt-0.5 text-[11px] text-muted-foreground">{{ review.created_at }}<span v-if="!review.is_admin" class="text-primary"> · {{ t("labels.product.verified_purchase") }}</span></p>
                                            </div>
                                            <div class="flex gap-0.5"><Star v-for="star in 5" :key="star" class="h-3.5 w-3.5" :class="star <= review.rating ? 'fill-primary text-primary' : 'text-border'" aria-hidden="true" /></div>
                                        </div>
                                        <p v-if="review.review" class="mt-2 text-xs leading-5 text-muted-foreground">{{ review.review }}</p>
                                    </article>
                                    <p v-if="!reviews.length" class="text-sm text-muted-foreground">{{ t("labels.product.no_reviews") }}</p>
                                </div>
                                <div v-if="reviewsError" class="rounded-xl bg-secondary px-3.5 py-3 text-sm text-muted-foreground">{{ t("labels.product.reviews_load_failed") }}</div>
                                <Button v-if="hasMoreReviews" type="button" class="w-full border-primary bg-white text-primary hover:bg-primary/5" :loading="isLoadingReviews" @click="loadMoreReviews">
                                    {{ t("labels.product.load_more_reviews") }}
                                </Button>
                            </template>
                        </div>
                    </div>

                <div class="p-4 md:hidden">
                    <div class="mt-4 divide-y divide-border border-y border-border">
                            <div v-for="section in mobileDetailSections" :key="section.key">
                                <Button
                                    type="button"
                                    :aria-expanded="activeMobileDetail === section.key"
                                    :aria-controls="'mobile-detail-' + section.key"
                                    class="flex min-h-14 w-full items-center justify-between rounded-none border-0 bg-transparent px-1 py-3 text-left hover:bg-transparent focus-visible:ring-2 focus-visible:ring-primary/30"
                                    @click="toggleMobileDetail(section.key)"
                                >
                                    <span class="flex items-center gap-3 text-sm font-semibold text-foreground">
                                        <component :is="section.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                        {{ section.label }}
                                    </span>
                                    <ChevronUp v-if="activeMobileDetail === section.key" class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                                    <ChevronDown v-else class="h-4 w-4 text-muted-foreground" aria-hidden="true" />
                                </Button>

                                <div v-show="activeMobileDetail === section.key" :id="'mobile-detail-' + section.key" class="pb-4">
                                    <div v-if="section.key === 'description' && product.description" class="prose prose-sm max-w-none text-sm leading-6 text-muted-foreground" v-html="product.description"></div>
                                    <p v-else-if="section.key === 'description'" class="text-sm text-muted-foreground">{{ t("labels.product.description_unavailable") }}</p>
                                    <dl v-else-if="section.key === 'specifications'" class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 rounded-xl bg-secondary/45 p-4 text-sm">
                                        <template v-for="spec in specRows" :key="spec.label">
                                            <dt class="text-muted-foreground">{{ spec.label }}</dt>
                                            <dd class="font-medium text-foreground">{{ spec.value }}</dd>
                                        </template>
                                    </dl>
                                    <div v-else>
                                        <div v-if="!ratingData" class="flex min-h-24 items-center justify-center text-sm text-muted-foreground" aria-live="polite">
                                            {{ isLoadingReviews ? t("labels.product.loading_reviews") : t("labels.product.reviews_loading_short") }}
                                        </div>
                                        <template v-else>
                                            <div class="flex items-center gap-4 rounded-xl bg-secondary/45 p-4">
                                                <div class="w-20 shrink-0 text-center">
                                                    <p class="text-3xl font-bold text-foreground">{{ ratingData.summary.average.toFixed(1) }}</p>
                                                    <div class="mt-1 flex justify-center gap-0.5">
                                                        <Star v-for="star in 5" :key="star" class="h-3.5 w-3.5" :class="star <= Math.round(ratingData.summary.average) ? 'fill-primary text-primary' : 'text-border'" aria-hidden="true" />
                                                    </div>
                                                    <p class="mt-1 text-[11px] text-muted-foreground">{{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }}</p>
                                                </div>
                                                <div class="min-w-0 flex-1 space-y-1.5">
                                                    <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-2 text-xs">
                                                        <span class="w-5 text-muted-foreground">{{ rating }}</span>
                                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full bg-primary" :style="{ width: ratingPercent(rating) + '%' }"></div></div>
                                                        <span class="w-5 text-right text-muted-foreground">{{ ratingData.summary.distribution?.[rating] || 0 }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                                <Button
                                                    v-for="rating in ['all', 5, 4, 3, 2, 1]"
                                                    :key="rating"
                                                    type="button"
                                                    :aria-pressed="selectedReviewRating === rating"
                                                    :class="cn(
                                                        'shrink-0 rounded-full px-3 py-1.5 text-xs focus-visible:ring-2 focus-visible:ring-primary/30',
                                                        selectedReviewRating === rating ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-white text-muted-foreground hover:border-primary hover:text-primary',
                                                    )"
                                                    @click="filterReviews(rating)"
                                                >
                                                    <template v-if="rating === 'all'">{{ t("labels.product.all") }} ({{ formatProductCount(ratingData.summary.count) }})</template>
                                                    <template v-else>{{ t("labels.product.stars", { rating }) }} ({{ formatProductCount(ratingData.summary.distribution?.[rating] || 0) }})</template>
                                                </Button>
                                            </div>
                                            <div class="mt-4 space-y-3">
                                                <article v-for="review in reviews.slice(0, 2)" :key="review.id" class="rounded-xl border border-border p-3">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="text-xs font-semibold text-foreground">{{ review.reviewer_name }}</p>
                                                        <div class="flex gap-0.5"><Star v-for="star in 5" :key="star" class="h-3 w-3" :class="star <= review.rating ? 'fill-primary text-primary' : 'text-border'" aria-hidden="true" /></div>
                                                    </div>
                                                    <p v-if="review.review" class="mt-1.5 text-xs leading-5 text-muted-foreground">{{ review.review }}</p>
                                                </article>
                                            </div>
                                            <Button v-if="hasMoreReviews" type="button" class="mt-4 w-full border-primary bg-white text-primary hover:bg-primary/5" :loading="isLoadingReviews" @click="loadMoreReviews">
                                                {{ t("labels.product.load_more_reviews") }}
                                            </Button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section ref="ratingSection" class="hidden min-w-0 rounded-3xl border border-border bg-white p-5 md:block md:p-6" :aria-label="t('labels.product.reviews_heading')">
                    <div v-if="!ratingData" class="flex min-h-40 items-center justify-center text-center" aria-live="polite">
                        <div v-if="reviewsError" class="flex flex-col items-center gap-4">
                            <p class="text-sm text-muted-foreground">{{ t("labels.product.reviews_load_failed") }}</p>
                            <Button type="button" class="border-primary bg-white text-primary hover:bg-primary/5" @click="fetchReviews(1, true)">{{ t("labels.actions.retry") }}</Button>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">{{ t("labels.product.loading_reviews") }}</p>
                    </div>
                    <template v-else>
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-bold text-foreground">{{ t("labels.product.reviews_heading") }}</h2>
                            <span class="text-sm text-muted-foreground">{{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }}</span>
                        </div>
                        <p v-if="hasPositiveRatingSummary" class="mt-4 rounded-xl bg-amber-50 px-3.5 py-3 text-sm text-amber-800">
                            {{ t("labels.product.rated_as") }} <strong>{{ t("labels.product.good_quality") }}</strong>
                        </p>
                        <div class="mt-5 flex items-center gap-4 rounded-2xl bg-secondary/45 p-4">
                            <div class="w-20 shrink-0 text-center">
                                <p class="text-3xl font-bold text-foreground">{{ ratingData.summary.average.toFixed(1) }}</p>
                                <div class="mt-1.5 flex justify-center gap-0.5">
                                    <Star v-for="star in 5" :key="star" class="h-3.5 w-3.5" :class="star <= Math.round(ratingData.summary.average) ? 'fill-primary text-primary' : 'text-border'" aria-hidden="true" />
                                </div>
                                <p class="mt-1.5 text-xs text-muted-foreground">{{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }}</p>
                            </div>
                            <div class="min-w-0 flex-1 space-y-2">
                                <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-2 text-xs">
                                    <span class="flex w-5 items-center gap-0.5 text-muted-foreground">{{ rating }}<Star class="h-3 w-3 fill-primary text-primary" aria-hidden="true" /></span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-border"><div class="h-full rounded-full bg-primary" :style="{ width: ratingPercent(rating) + '%' }"></div></div>
                                    <span class="w-5 text-right text-muted-foreground">{{ ratingData.summary.distribution?.[rating] || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </section>
            </div>

            <Deferred data="relatedProducts">
                <section v-if="relatedItems.length" class="mt-8" :aria-label="t('labels.product.related_products')">
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">{{ t("labels.product.related_eyebrow") }}</p>
                            <h2 class="mt-1 text-xl font-bold tracking-[-0.02em] text-foreground md:text-2xl">{{ t("labels.product.related_products") }}</h2>
                        </div>
                        <Link :href="route('frontend.products', product.category ? { category: product.category.slug } : {})" class="hidden items-center gap-1 rounded-sm text-sm font-semibold text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 sm:flex">
                            {{ t("labels.product.view_all_products") }} <ChevronRight class="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </div>
                    <div class="scrollbar-hidden flex touch-pan-x snap-x snap-mandatory gap-3 overflow-x-auto overscroll-x-contain pb-2 sm:gap-4 md:gap-5 lg:grid lg:grid-cols-6 lg:gap-3 lg:overflow-visible lg:pb-0">
                        <ProductRecommendationCard
                            v-for="related in relatedItems"
                            :key="related.id"
                            :product="related"
                            class="w-[min(40vw,10rem)] shrink-0 snap-start sm:w-40 md:w-44 lg:w-auto lg:max-w-[15rem] lg:shrink lg:justify-self-center"
                        />
                    </div>
                </section>
                <template #fallback>
                    <section class="mt-8" :aria-label="t('labels.product.related_products')">
                        <div class="bg-secondary text-muted-foreground flex min-h-28 items-center justify-center px-4 py-8 text-sm" role="status" aria-live="polite">
                            {{ t("labels.actions.loading") }}
                        </div>
                    </section>
                </template>
            </Deferred>

            <section v-if="product.faqs?.length" class="mt-8" :aria-label="t('labels.faq.heading')">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-foreground md:text-2xl">{{ t("labels.faq.heading") }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t("labels.faq.subheading", { name: product.name }) }}</p>
                </div>
                <div class="mx-auto max-w-3xl space-y-2">
                    <div v-for="(faq, index) in product.faqs" :key="faq.id" class="overflow-hidden rounded-2xl border border-border bg-white">
                        <Button type="button" :aria-expanded="activeFaq === index" class="flex w-full items-center justify-between rounded-none border-0 bg-transparent p-4 text-left hover:bg-secondary/50 focus-visible:ring-2 focus-visible:ring-primary/30" @click="toggleFaq(index)">
                            <span class="pr-4 text-sm font-semibold text-foreground">{{ faq.question }}</span>
                            <Plus v-if="activeFaq !== index" class="h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                            <Minus v-else class="h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                        </Button>
                        <div v-show="activeFaq === index" class="px-4 pb-4">
                            <div class="prose prose-sm max-w-none text-sm leading-6 text-muted-foreground" v-html="faq.answer"></div>
                        </div>
                    </div>
                </div>
            </section>

        </PageShell>

        <Teleport to="body">
            <div
                v-if="isImageZoomOpen && selectedImage"
                ref="zoomDialog"
                role="dialog"
                aria-modal="true"
                :aria-label="t('labels.product.view_image', { number: selectedImageNumber })"
                tabindex="-1"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-foreground/90 p-4 backdrop-blur-sm sm:p-8"
                @click.self="closeImageZoom"
                @keydown.esc="closeImageZoom"
            >
                <Button type="button" :aria-label="t('labels.product.close_image_viewer')" class="absolute top-4 right-4 z-10 h-11 w-11 rounded-full border-0 bg-white/95 p-0 text-foreground hover:bg-white focus-visible:ring-2 focus-visible:ring-white" @click="closeImageZoom">
                    <X class="h-5 w-5" aria-hidden="true" />
                </Button>
                <img :src="selectedImage" :alt="product.name" decoding="async" class="max-h-full max-w-full rounded-xl object-contain shadow-2xl" />
            </div>
        </Teleport>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-white/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(0,0,0,0.08)] backdrop-blur md:hidden">
            <div class="mx-auto grid max-w-md grid-cols-2 gap-3">
                <Button type="button" :aria-label="t('labels.actions.add_to_cart')" :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)" class="whitespace-nowrap border-primary bg-white px-2 text-sm text-primary hover:bg-primary/5" @click="addToCart">
                    <ShoppingBag class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ t("labels.actions.add_to_cart_short") }}
                </Button>
                <Button type="button" :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)" class="whitespace-nowrap border-primary bg-primary px-3 text-sm text-primary-foreground hover:bg-primary/90" @click="buyNow">
                    {{ t("labels.product.buy_now") }}
                </Button>
            </div>
        </div>
    </TemplateWrapper>
</template>

<style scoped>
.product-detail-grid,
.product-detail-lower {
    grid-template-columns: minmax(0, 1fr);
}

@media (min-width: 768px) {
    .product-detail-grid {
        grid-template-columns: minmax(0, 1.2fr) minmax(22rem, 0.8fr);
    }

    .product-detail-grid--single-image {
        grid-template-columns: minmax(0, 1fr) minmax(22rem, 1.1fr);
    }

    .product-detail-lower {
        grid-template-columns: minmax(0, 1.45fr) minmax(18rem, 0.75fr);
    }
}

</style>
