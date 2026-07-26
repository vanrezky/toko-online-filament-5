<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import { Link, usePage, router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import { formatCompactNumber, formatCurrency } from "../../lib/utils";
import {
    ShoppingBag,
    Heart,
    ShieldCheck,
    Truck,
    RefreshCw,
    ChevronRight,
    ChevronDown,
    ChevronUp,
    Plus,
    Minus,
    MapPin,
    Scale,
    Star,
    Tag,
    MessageCircle,
    ZoomIn,
    X,
} from "lucide-vue-next";

const props = defineProps({
    product: Object,
});

const page = usePage();
const selectedImage = ref(props.product.thumbnail);
const isImageZoomOpen = ref(false);
const zoomDialog = ref(null);
const imageZoomTrigger = ref(null);
const galleryThumbnails = ref(null);
const thumbnailElements = ref([]);
const visibleThumbnailIndexes = ref(new Set([0]));
const quantity = ref(1);
const selectedAttributes = ref({});
const activeFaq = ref(null);
const isDescriptionExpanded = ref(false);
const reviews = ref([]);
const ratingData = ref(null);
const ratingSection = ref(null);
const reviewPage = ref(0);
const isLoadingReviews = ref(false);
const hasMoreReviews = ref(true);
const selectedReviewRating = ref("all");
const reviewsError = ref(false);
const { t, locale } = useI18n();

const formatProductCount = (count) => formatCompactNumber(count, locale.value);
const localeCode = computed(() => (locale.value === "id" ? "id-ID" : "en-US"));
const selectedImageNumber = computed(() => {
    const imageIndex = props.product.images?.indexOf(selectedImage.value) ?? -1;
    return imageIndex >= 0 ? imageIndex + 1 : 1;
});

const openImageZoom = async () => {
    isImageZoomOpen.value = true;
    await nextTick();
    zoomDialog.value?.focus();
};

const closeImageZoom = async () => {
    isImageZoomOpen.value = false;
    await nextTick();
    imageZoomTrigger.value?.focus();
};

const isWishlisted = computed(() => {
    return page.props.wishlist_product_ids?.includes(props.product.id);
});

const toggleWishlist = () => {
    router.post(
        route("frontend.wishlist.toggle"),
        {
            product_id: props.product.id,
        },
        {
            preserveScroll: true,
        },
    );
};

const hasVariants = computed(() => props.product.variants && props.product.variants.length > 0);

const attributeGroups = computed(() => {
    if (!hasVariants.value) return {};

    const groups = {};
    props.product.variants.forEach((variant) => {
        variant.attributes.forEach((attr) => {
            if (!groups[attr.name]) {
                groups[attr.name] = new Set();
            }
            groups[attr.name].add(attr.option);
        });
    });

    Object.keys(groups).forEach((key) => {
        groups[key] = Array.from(groups[key]);
    });

    return groups;
});

const selectedVariant = computed(() => {
    if (!hasVariants.value) return null;

    const selectedKeys = Object.keys(selectedAttributes.value);
    if (selectedKeys.length !== Object.keys(attributeGroups.value).length) return null;

    return props.product.variants.find((variant) => {
        return variant.attributes.every((attr) => selectedAttributes.value[attr.name] === attr.option);
    });
});

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
            .filter((w) => w.min_qty > minOrder)
            .sort((a, b) => b.min_qty - a.min_qty)
            .find((w) => quantity.value >= w.min_qty);

        if (applicableWholesale) return parseFloat(applicableWholesale.price);
    }

    return basePrice;
});

const displayOriginalPrice = computed(() => {
    return selectedVariant.value
        ? parseFloat(selectedVariant.value.price)
        : parseFloat(props.product.pricing?.original_price ?? props.product.price);
});

const activeWholesale = computed(() => {
    if (activeFlashsale.value) return null;
    if (!props.product.wholesales || props.product.wholesales.length === 0) return null;
    const minOrder = props.product.min_order || 1;
    return [...props.product.wholesales]
        .filter((w) => w.min_qty > minOrder)
        .sort((a, b) => b.min_qty - a.min_qty)
        .find((w) => quantity.value >= w.min_qty);
});

const nextWholesale = computed(() => {
    if (activeFlashsale.value) return null;
    if (!props.product.wholesales || props.product.wholesales.length === 0) return null;
    return [...props.product.wholesales].sort((a, b) => a.min_qty - b.min_qty).find((w) => quantity.value < w.min_qty);
});

const displayStock = computed(() => {
    const productStock = selectedVariant.value ? selectedVariant.value.stock : props.product.stock;
    return activeFlashsale.value ? Math.min(productStock, activeFlashsale.value.stock) : productStock;
});

const isSale = computed(() => ['sale', 'flashsale'].includes(props.product.pricing?.source));
const requiredAttributes = computed(() => Object.keys(attributeGroups.value));
const hasPositiveRatingSummary = computed(() => ratingData.value?.summary.count > 0 && ratingData.value.summary.average >= 4);

const seoTitle = computed(() => props.product.meta?.title || props.product.name);
const seoDescription = computed(() => props.product.meta?.description || props.product.description?.substring(0, 160));
const seoKeywords = computed(() => props.product.meta?.keyword);

const updateQuantity = (val) => {
    const maxStock = selectedVariant.value ? selectedVariant.value.stock : props.product.stock;
    const newQty = quantity.value + val;
    if (newQty >= (props.product.min_order || 1) && newQty <= maxStock) {
        quantity.value = newQty;
    }
};

const selectAttribute = (name, option) => {
    selectedAttributes.value[name] = option;
    quantity.value = 1;
};

const toggleFaq = (index) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const ratingPercent = (rating) => {
    const total = ratingData.value?.summary.count || 0;
    return total ? ((ratingData.value.summary.distribution?.[rating] || 0) / total) * 100 : 0;
};

const fetchReviews = async (page = 1, replace = false) => {
    if (isLoadingReviews.value || !hasMoreReviews.value) return;

    isLoadingReviews.value = true;
    reviewsError.value = false;
    try {
        const response = await axios.get(route("frontend.products.reviews", props.product.slug), {
            params: {
                page,
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

    if (rating === "all") {
        fetchReviews(1, true);
        return;
    }

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

                visibleThumbnailIndexes.value = new Set([...visibleThumbnailIndexes.value, Number(entry.target.dataset.index)]);
                thumbnailObserver.unobserve(entry.target);
            });
        },
        { root: galleryThumbnails.value, rootMargin: "0px 120px" },
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
        },
    );
};

const buyNow = () => {
    router.post(
        route("frontend.cart.store"),
        {
            product_id: props.product.id,
            product_variant_id: selectedVariant.value?.id,
            quantity: quantity.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => router.visit(route("frontend.checkout")),
        },
    );
};

</script>

<template>
    <TemplateWrapper :shell="false" :title="seoTitle" :description="seoDescription" :keywords="seoKeywords" :social-image="product.thumbnail">
        <PageShell container class="pb-24 sm:pb-0">
                <nav class="text-muted-foreground mb-6 hidden items-center gap-2 text-xs sm:mb-8 sm:flex">
                    <Link :href="route('frontend.home')" class="hover:text-foreground transition-colors">{{ t("labels.breadcrumb.home") }}</Link>
                    <ChevronRight class="h-3 w-3" />
                    <Link :href="route('frontend.products')" class="hover:text-foreground transition-colors">{{
                        t("labels.breadcrumb.products")
                    }}</Link>
                    <ChevronRight v-if="product.category" class="h-3 w-3" />
                    <Link
                        v-if="product.category"
                        :href="route('frontend.products', { category: product.category.slug })"
                        class="hover:text-foreground transition-colors"
                    >
                        {{ product.category.name }}
                    </Link>
                </nav>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
                    <div class="space-y-3 lg:sticky lg:top-20 lg:self-start">
                        <Button
                            ref="imageZoomTrigger"
                            @click="openImageZoom"
                            :aria-label="t('labels.product.view_image', { number: selectedImageNumber })"
                            aria-haspopup="dialog"
                            class="border-border group relative block aspect-square w-full overflow-hidden rounded-2xl border bg-white p-0 shadow-md transition-shadow hover:shadow-lg focus-visible:ring-offset-4 lg:max-w-[560px]"
                        >
                            <img
                                :src="selectedImage || 'https://placehold.co/800x1000?text=No+Image'"
                                :alt="product.name"
                                fetchpriority="high"
                                decoding="async"
                                class="h-full w-full object-cover"
                            />
                            <span
                                class="text-foreground pointer-events-none absolute right-4 bottom-4 hidden h-10 w-10 items-center justify-center rounded-full bg-white/95 opacity-0 shadow-sm transition-all duration-200 group-hover:opacity-100 group-focus-visible:opacity-100 sm:flex"
                                aria-hidden="true"
                            >
                                <ZoomIn class="h-5 w-5" />
                            </span>
                        </Button>
                        <div
                            v-if="product.images && product.images.length > 1"
                            ref="galleryThumbnails"
                            class="flex [scrollbar-width:none] gap-2 overflow-x-auto pb-1 sm:gap-3 [&::-webkit-scrollbar]:hidden"
                        >
                            <Button
                                v-for="(image, index) in product.images"
                                :key="index"
                                :ref="(element) => setThumbnailElement(element?.$el || element, index)"
                                :data-index="index"
                                @click="selectedImage = image"
                                :aria-label="t('labels.product.view_image', { number: index + 1 })"
                                :aria-pressed="selectedImage === image"
                                class="border-border h-16 w-16 shrink-0 overflow-hidden rounded-lg border bg-white shadow-sm transition-all hover:shadow-md sm:h-20 sm:w-20 sm:rounded-xl"
                                :class="
                                    selectedImage === image
                                        ? 'border-primary shadow-sm'
                                        : 'hover:border-primary/40 opacity-60 hover:opacity-100'
                                "
                            >
                                <img
                                    v-if="visibleThumbnailIndexes.has(index)"
                                    :src="image"
                                    :alt="`${product.name} ${index + 1}`"
                                    loading="lazy"
                                    decoding="async"
                                    fetchpriority="low"
                                    class="h-full w-full object-cover"
                                />
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-4 sm:space-y-6">
                        <div class="space-y-5 rounded-2xl bg-white p-5 sm:p-6">
                            <div class="text-muted-foreground flex flex-wrap items-center gap-2 text-xs">
                                <span v-if="product.category" class="flex items-center gap-1.5"><Tag class="text-primary h-3.5 w-3.5" />{{ product.category.name }}</span>
                                <span v-if="product.category" class="text-border">|</span>
                                <span class="flex items-center gap-1.5"
                                    ><MapPin class="text-primary h-3.5 w-3.5" />
                                    {{ product.warehouse?.name || t("labels.product.default_warehouse") }}</span
                                >
                                <span class="text-border">|</span>
                                <span class="flex items-center gap-1.5"
                                    ><Scale class="text-primary h-3.5 w-3.5" />
                                    {{ t("labels.product.weight_unit", { weight: ((product.weight || 0) / 1000).toFixed(2) }) }}</span
                                >
                            </div>
                            <h1 class="text-foreground text-2xl font-bold md:text-3xl">
                                {{ product.name }}
                            </h1>
                            <div v-if="ratingData" class="border-border flex flex-wrap items-center gap-2.5 border-b pb-4 text-sm">
                                <span class="text-muted-foreground">{{ formatProductCount(product.sold_count) }} {{ t("labels.product.sold") }}</span>
                                <span class="text-border">|</span>
                                <span class="text-muted-foreground"
                                    >({{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }})</span
                                >
                                <span class="text-border">|</span>
                                <span class="text-foreground flex items-center gap-1 font-bold"
                                    ><Star class="h-4 w-4 fill-[#e8a167] text-[#e8a167]" /> {{ ratingData.summary.average.toFixed(1) }}</span
                                >
                                <span class="text-border">|</span>
                                <Button @click="toggleWishlist" class="text-muted-foreground hover:text-primary ml-auto flex items-center gap-1 transition-colors">
                                    <Heart class="h-4 w-4" :class="isWishlisted ? 'fill-red-500 text-red-500' : ''" />
                                    {{ isWishlisted ? t("labels.product.saved") : t("labels.product.save") }}
                                </Button>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <template v-if="isSale && !activeWholesale">
                                    <span class="text-primary text-3xl font-bold">{{ formatCurrency(displayPrice, localeCode) }}</span>
                                    <span class="text-muted-foreground text-lg line-through">{{ formatCurrency(displayOriginalPrice, localeCode) }}</span>
                                    <span v-if="activeFlashsale || product.discount_percentage" class="rounded-full bg-red-500 px-3 py-1 text-xs font-bold text-white">
                                        {{ t("labels.product.discount_off", { discount: activeFlashsale?.discount_percentage ?? product.discount_percentage }) }}
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-primary text-3xl font-bold">{{ formatCurrency(displayPrice, localeCode) }}</span>
                                </template>
                            </div>

                            <p v-if="activeWholesale" class="rounded-xl bg-green-50 p-3 text-sm font-medium text-green-700">
                                {{
                                    t("labels.product.wholesale_active", {
                                        qty: activeWholesale.min_qty,
                                        price: formatCurrency(activeWholesale.price, localeCode),
                                    })
                                }}
                            </p>
                            <p v-else-if="nextWholesale" class="bg-secondary text-muted-foreground rounded-xl p-3 text-sm">
                                {{
                                    t("labels.product.wholesale_next", {
                                        qty: nextWholesale.min_qty,
                                        price: formatCurrency(nextWholesale.price, localeCode),
                                    })
                                }}
                            </p>

                            <div v-if="hasVariants" class="border-border space-y-4 border-t pt-4">
                                <div v-for="(options, name) in attributeGroups" :key="name" class="space-y-3">
                                    <label class="flex items-center justify-between text-sm font-semibold">
                                        <span>{{ name }}</span>
                                        <span v-if="selectedAttributes[name]" class="text-muted-foreground font-normal">{{
                                            selectedAttributes[name]
                                        }}</span>
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            v-for="option in options"
                                            :key="option"
                                            @click="selectAttribute(name, option)"
                                            :aria-pressed="selectedAttributes[name] === option"
                                            class="rounded-lg border-2 px-4 py-2 text-sm font-semibold transition-all"
                                            :class="
                                                selectedAttributes[name] === option
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-border text-foreground hover:border-primary/50 bg-white'
                                            "
                                        >
                                            {{ option }}
                                        </Button>
                                    </div>
                                </div>
                                <p v-if="!selectedVariant" id="product-option-guidance" class="text-muted-foreground text-sm" aria-live="polite">
                                    {{ t("labels.product.select_options_hint", { options: requiredAttributes.join(", ") }) }}
                                </p>
                            </div>

                            <div class="border-border border-t pt-4">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <label for="product-quantity" class="text-sm font-semibold">{{ t("labels.product.quantity") }}</label>
                                        <p :class="displayStock > 0 ? 'text-green-600' : 'text-red-500'" class="mt-1 text-sm">
                                            {{
                                                displayStock > 0
                                                    ? t("labels.product.stock", { stock: displayStock })
                                                    : t("labels.product.out_of_stock")
                                            }}
                                        </p>
                                    </div>
                                    <div class="border-border flex w-fit items-center rounded-xl border bg-white">
                                        <Button
                                            @click="updateQuantity(-1)"
                                            :aria-label="t('labels.product.decrease_quantity')"
                                            class="hover:bg-secondary flex h-11 w-11 items-center justify-center rounded-l-xl transition-colors"
                                            :disabled="quantity <= (product.min_order || 1)"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </Button>
                                        <FormInput
                                            type="number"
                                            id="product-quantity"
                                            v-model="quantity"
                                            readonly
                                            wrapper-class="w-14"
                                            class="h-11 rounded-none border-x border-y-0 bg-transparent px-0 text-center font-semibold"
                                        />
                                        <Button
                                            @click="updateQuantity(1)"
                                            :aria-label="t('labels.product.increase_quantity')"
                                            class="hover:bg-secondary flex h-11 w-11 items-center justify-center rounded-r-xl transition-colors"
                                            :disabled="quantity >= displayStock"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <p v-if="product.min_order && product.min_order > 1" class="text-muted-foreground text-xs">
                                    {{ t("labels.product.min_order", { min: product.min_order }) }}
                                </p>
                            </div>
                        </div>

                        <div class="divide-border divide-y rounded-2xl border border-border bg-white px-5 sm:px-6">
                            <div class="flex items-center gap-4 py-5">
                                <Truck class="text-primary h-6 w-6 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm font-semibold">{{ t("labels.trust.free_shipping") }}</h4>
                                    <p class="text-muted-foreground mt-1 text-xs">{{ t("labels.trust.free_shipping_note") }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 py-5">
                                <ShieldCheck class="text-primary h-6 w-6 shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm font-semibold">{{ t("labels.trust.secure_payment") }}</h4>
                                    <p class="text-muted-foreground mt-1 text-xs">{{ t("labels.trust.secure_payment_note") }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="hidden space-y-3 rounded-2xl bg-white p-2 sm:block">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <Button
                                    @click="addToCart"
                                    :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)"
                                    class="border-primary text-primary hover:bg-primary/5 flex items-center justify-center gap-2 rounded-full border-2 px-5 py-3.5 text-sm font-bold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <ShoppingBag class="h-5 w-5" />
                                    <span>{{
                                        hasVariants && !selectedVariant ? t("labels.actions.select_options") : t("labels.actions.add_to_cart")
                                    }}</span>
                                </Button>
                                <Button
                                    @click="buyNow"
                                    :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)"
                                    class="bg-primary text-primary-foreground hover:bg-primary/90 flex items-center justify-center rounded-full px-5 py-3.5 text-sm font-bold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ t("labels.product.buy_now") }}
                                </Button>
                            </div>
                        </div>

                        <section v-if="product.description" class="rounded-2xl bg-white p-6">
                            <h2 class="text-foreground text-xl font-bold md:text-2xl">{{ t("labels.product.description") }}</h2>
                            <div
                                class="prose prose-sm text-muted-foreground mt-4 max-w-none overflow-hidden text-base leading-relaxed transition-[max-height] duration-300 ease-in-out motion-reduce:transition-none"
                                :class="isDescriptionExpanded ? 'max-h-none' : 'max-h-[4.5rem]'"
                                v-html="product.description"
                            ></div>
                            <Button
                                type="button"
                                @click="isDescriptionExpanded = !isDescriptionExpanded"
                                class="text-primary hover:text-primary/80 mt-4 flex items-center gap-1.5 text-sm font-semibold transition-colors"
                            >
                                <ChevronUp v-if="isDescriptionExpanded" class="h-4 w-4" />
                                <ChevronDown v-else class="h-4 w-4" />
                                {{ isDescriptionExpanded ? t("labels.product.show_less") : t("labels.product.read_more") }}
                            </Button>
                        </section>
                    </div>
                </div>

                <Teleport to="body">
                    <div
                        v-if="isImageZoomOpen"
                        ref="zoomDialog"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="t('labels.product.view_image', { number: selectedImageNumber })"
                        tabindex="-1"
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-foreground/90 p-4 backdrop-blur-sm sm:p-8"
                        @click.self="closeImageZoom"
                        @keydown.esc="closeImageZoom"
                    >
                        <Button
                            @click="closeImageZoom"
                            :aria-label="t('labels.product.close_image_viewer')"
                            class="absolute top-4 right-4 z-10 rounded-full bg-white/95 p-2 text-foreground shadow-md hover:bg-white sm:top-6 sm:right-6"
                        >
                            <X class="h-5 w-5" />
                        </Button>
                        <img
                            :src="selectedImage || 'https://placehold.co/800x1000?text=No+Image'"
                            :alt="product.name"
                            decoding="async"
                            class="max-h-full max-w-full rounded-xl object-contain shadow-2xl"
                        />
                    </div>
                </Teleport>

                <div
                    class="border-border fixed inset-x-0 bottom-0 z-40 border-t bg-white/95 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(0,0,0,0.08)] backdrop-blur sm:hidden"
                >
                    <div class="container mx-auto grid grid-cols-2 gap-3">
                        <Button
                            @click="addToCart"
                            :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)"
                            class="border-primary text-primary flex items-center justify-center gap-2 rounded-full border-2 px-4 py-3 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <ShoppingBag class="h-5 w-5" />
                            <span>{{ t("labels.header.cart") }}</span>
                        </Button>
                        <Button
                            @click="buyNow"
                            :disabled="displayStock <= 0 || (hasVariants && !selectedVariant)"
                            class="bg-primary text-primary-foreground rounded-full px-4 py-3 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ t("labels.product.buy_now") }}
                        </Button>
                    </div>
                </div>

                <section ref="ratingSection" class="mt-12 rounded-2xl bg-white px-4 py-5 md:mt-16 md:px-6 md:py-6">
                    <div v-if="!ratingData" class="flex min-h-40 items-center justify-center text-center" aria-live="polite">
                        <div v-if="reviewsError" class="flex flex-col items-center gap-4">
                            <p class="text-muted-foreground text-sm">{{ t("labels.product.reviews_load_failed") }}</p>
                            <Button @click="fetchReviews(1, true)" variant="outline" class="rounded-full">
                                {{ t("labels.actions.retry") }}
                            </Button>
                        </div>
                        <p v-else class="text-muted-foreground text-sm">{{ t("labels.product.loading_reviews") }}</p>
                    </div>
                    <template v-else>
                        <div class="mb-6 flex items-center gap-2">
                            <MessageCircle class="text-primary h-5 w-5" />
                            <h2 class="text-foreground text-xl font-bold md:text-2xl">
                                {{ t("labels.product.reviews_heading") }} <span class="font-normal">({{ formatProductCount(ratingData.summary.count) }})</span>
                            </h2>
                        </div>
                        <p v-if="hasPositiveRatingSummary" class="mb-5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            💬 {{ t("labels.product.rated_as") }} <strong>{{ t("labels.product.good_quality") }}</strong>
                        </p>
                        <div class="bg-secondary/50 flex items-center gap-4 rounded-2xl p-4 sm:gap-5 sm:p-5">
                            <div class="w-24 shrink-0 text-center sm:w-28">
                                <p class="text-foreground text-3xl font-bold sm:text-4xl">{{ ratingData.summary.average.toFixed(1) }}</p>
                                <div class="mt-1.5 flex justify-center gap-0.5">
                                    <Star
                                        v-for="star in 5"
                                        :key="star"
                                        class="h-3.5 w-3.5 sm:h-4 sm:w-4"
                                        :class="star <= Math.round(ratingData.summary.average) ? 'fill-[#e8a167] text-[#e8a167]' : 'text-gray-300'"
                                    />
                                </div>
                                <p class="text-muted-foreground mt-1.5 text-xs sm:text-sm">
                                    {{ formatProductCount(ratingData.summary.count) }} {{ t("labels.product.reviews") }}
                                </p>
                            </div>
                            <div class="min-w-0 flex-1 space-y-2.5">
                                <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-3 text-sm">
                                    <span class="text-muted-foreground w-7"
                                        >{{ rating }}<Star class="inline h-3.5 w-3.5 fill-[#e8a167] text-[#e8a167]"
                                    /></span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                                        <div class="h-full rounded-full bg-[#e8a167]" :style="{ width: `${ratingPercent(rating)}%` }"></div>
                                    </div>
                                    <span class="text-muted-foreground w-6 text-right">{{ ratingData.summary.distribution?.[rating] || 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex [scrollbar-width:none] gap-2 overflow-x-auto pb-1 [&::-webkit-scrollbar]:hidden">
                            <Button
                                v-for="rating in ['all', 5, 4, 3, 2, 1]"
                                :key="rating"
                                @click="filterReviews(rating)"
                                :aria-pressed="selectedReviewRating === rating"
                                class="shrink-0 rounded-full border px-3 py-1.5 text-xs whitespace-nowrap transition-colors sm:px-4 sm:py-2 sm:text-sm"
                                :class="
                                    selectedReviewRating === rating
                                        ? 'border-primary bg-primary font-semibold text-primary-foreground'
                                        : 'border-border text-muted-foreground bg-white hover:border-primary hover:text-primary'
                                "
                            >
                                <template v-if="rating === 'all'">{{ t("labels.product.all") }} ({{ formatProductCount(ratingData.summary.count) }})</template>
                                <template v-else
                                    >{{ t("labels.product.stars", { rating }) }} ({{ formatProductCount(ratingData.summary.distribution?.[rating] || 0) }})</template
                                >
                            </Button>
                        </div>
                        <div class="mt-6 grid gap-3 md:grid-cols-2">
                            <article v-for="review in reviews" :key="review.id" class="border-border rounded-lg border p-3.5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-foreground text-sm leading-tight font-semibold">{{ review.reviewer_name }}</p>
                                        <p class="text-muted-foreground mt-0.5 text-[11px] leading-tight">
                                            {{ review.created_at }}
                                            <span v-if="!review.is_admin" class="text-primary">• {{ t("labels.product.verified_purchase") }}</span>
                                        </p>
                                    </div>
                                    <div class="flex gap-0.5">
                                        <Star
                                            v-for="star in 5"
                                            :key="star"
                                            class="h-3.5 w-3.5"
                                            :class="star <= review.rating ? 'fill-[#e8a167] text-[#e8a167]' : 'text-gray-300'"
                                        />
                                    </div>
                                </div>
                                <p v-if="review.review" class="text-muted-foreground mt-2 text-xs leading-5">{{ review.review }}</p>
                                <div v-if="review.images?.length" class="mt-2.5 flex gap-2">
                                <img
                                    v-for="(image, imageIndex) in review.images"
                                    :key="image"
                                    :src="image"
                                    :alt="t('labels.product.review_image_alt', { number: imageIndex + 1 })"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-16 w-16 rounded-md object-cover"
                                />
                                </div>
                            </article>
                            <p v-if="!reviews.length" class="text-muted-foreground text-sm">{{ t("labels.product.no_reviews") }}</p>
                        </div>
                        <div v-if="reviewsError" class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-secondary px-4 py-3">
                            <p class="text-muted-foreground text-sm">{{ t("labels.product.reviews_load_failed") }}</p>
                            <Button @click="fetchReviews(reviewPage + 1)" variant="outline" size="sm" class="rounded-full">
                                {{ t("labels.actions.retry") }}
                            </Button>
                        </div>
                        <div v-if="hasMoreReviews" class="mt-6 text-center">
                            <Button
                                @click="loadMoreReviews"
                                :disabled="isLoadingReviews"
                                class="border-primary text-primary hover:bg-primary hover:text-primary-foreground rounded-full border-2 px-6 py-2.5 text-sm font-semibold transition-colors disabled:opacity-60"
                            >
                                {{ isLoadingReviews ? t("labels.product.loading_reviews") : t("labels.product.load_more_reviews") }}
                            </Button>
                        </div>
                    </template>
                </section>

                <div v-if="product.faqs && product.faqs.length > 0" class="mt-12 md:mt-16">
                    <div class="mb-6 text-center md:mb-8">
                        <h2 class="text-foreground text-xl font-bold md:text-2xl">{{ t("labels.faq.heading") }}</h2>
                        <p class="text-muted-foreground mt-2 text-sm">{{ t("labels.faq.subheading", { name: product.name }) }}</p>
                    </div>

                    <div class="mx-auto max-w-3xl space-y-3">
                        <div v-for="(faq, index) in product.faqs" :key="faq.id" class="overflow-hidden rounded-xl bg-white">
                            <Button
                                @click="toggleFaq(index)"
                                :aria-expanded="activeFaq === index"
                                class="hover:bg-secondary/50 flex w-full items-center justify-between p-5 text-left transition-colors"
                            >
                                <span class="pr-4 text-sm font-semibold">{{ faq.question }}</span>
                                <div class="bg-secondary flex h-7 w-7 shrink-0 items-center justify-center rounded-full">
                                    <Plus v-if="activeFaq !== index" class="text-foreground h-4 w-4" />
                                    <Minus v-else class="text-primary h-4 w-4" />
                                </div>
                            </Button>

                            <div v-show="activeFaq === index" class="px-5 pb-5">
                                <div class="prose prose-sm text-muted-foreground max-w-none text-sm leading-relaxed" v-html="faq.answer"></div>
                            </div>
                        </div>
                    </div>
                </div>

        </PageShell>
    </TemplateWrapper>
</template>
