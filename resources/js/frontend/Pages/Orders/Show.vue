<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed, getCurrentInstance, reactive, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormCheckbox from "../../components/UI/FormCheckbox.vue";
import FormFile from "../../components/UI/FormFile.vue";
import FormTextarea from "../../components/UI/FormTextarea.vue";
import Card from "../../components/UI/Card.vue";
import { formatCurrency, formatDate, formatPhone } from "../../lib/utils";
import { getOrderStatusColor, getOrderStatusLabel } from "../../lib/order-status";
import { useI18n } from "vue-i18n";
import { Package, ChevronLeft, MapPin, Truck, CreditCard, CheckCircle2, Store, Star, X } from "lucide-vue-next";

const props = defineProps({
    order: Object,
});
const { t } = useI18n();
const { proxy } = getCurrentInstance();
const reviewForms = reactive({});
const reviewDialogOpen = ref(false);

const reviewForm = (item) => {
    if (!reviewForms[item.id]) {
        reviewForms[item.id] = { rating: 5, review: '', is_anonymous: false, images: [] };
    }
    return reviewForms[item.id];
};

const reviewableProducts = computed(() => (props.order.products || []).filter((item) => !item.reviewed));

const openReviewDialog = () => {
    reviewableProducts.value.forEach(reviewForm);
    reviewDialogOpen.value = true;
};

const submitReviews = () => {
    router.post(route('frontend.orders.reviews.store', props.order.id), {
        reviews: reviewableProducts.value.map((item) => ({
            transaction_product_id: item.id,
            ...reviewForm(item),
        })),
    }, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            reviewableProducts.value.forEach((item) => { item.reviewed = true; });
            reviewDialogOpen.value = false;
        },
    });
};

const cancelOrder = () => {
    proxy.$confirm({
        title: "Batalkan Pesanan",
        message: "Apakah Anda yakin ingin membatalkan pesanan ini?",
        button: {
            no: "Tidak",
            yes: "Ya, Batalkan",
        },
        callback: (confirm) => {
            if (confirm) {
                router.post(route("frontend.orders.cancel", props.order.id), {}, {
                    preserveScroll: true,
                });
            }
        },
    });
};

const dateTimeFormat = { year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit" };

const getStatusDateLabel = (status) => {
    if (status === "cancelled") return t("labels.order.status_dates.cancelled");
    if (status === "completed") return t("labels.order.status_dates.completed");
    return t("labels.order.status_dates.shipped");
};

const subtotal = computed(() => {
    return props.order.products?.reduce((acc, p) => acc + p.price * p.quantity, 0) || 0;
});

const hasPickup = computed(() => {
    return props.order.shipping_groups?.some(group => group.is_pickup);
});

const hasDelivery = computed(() => {
    return props.order.shipping_groups?.some(group => !group.is_pickup);
});

const pickupGroups = computed(() => {
    return (props.order.shipping_groups || []).filter(group => group.is_pickup);
});

const deliveryGroups = computed(() => {
    return (props.order.shipping_groups || []).filter(group => !group.is_pickup);
});

const deliveryCouriers = computed(() => {
    const groups = props.order.shipping_groups || [];

    return groups
        .filter(group => !group.is_pickup)
        .map(group => group.courier_name)
        .filter(Boolean)
        .filter((value, index, self) => self.indexOf(value) === index)
        .join(', ');
});

const getPaymentLabel = () => {
    if (props.order.payment_type === 'installment' && props.order.installment_plan) {
        return `Cicilan ${props.order.installment_plan.tenor}x`;
    }
    return t('labels.payment.full');
};

const getGroupedProducts = () => {
    if (!props.order.shipping_groups || props.order.shipping_groups.length === 0) {
        return [{ is_pickup: false, products: props.order.products || [] }];
    }

    return props.order.shipping_groups.map(group => ({
        ...group,
        products: props.order.products?.filter(p => p.warehouse_id == group.warehouse_id) || [],
    }));
};

const statusDates = computed(() => {
    const items = [];

    if (props.order.delivery_date) {
        items.push({ key: "shipped", date: props.order.delivery_date });
    }
    if (props.order.complete_date) {
        items.push({ key: "completed", date: props.order.complete_date });
    }
    if (props.order.cancelled_at) {
        items.push({ key: "cancelled", date: props.order.cancelled_at });
    }

    return items;
});
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('meta.order_detail.title')">
        <PageShell>
            <div class="container mx-auto px-4 md:px-6">
                <div class="mx-auto max-w-5xl space-y-8">
                    <!-- Header -->
                    <div class="flex flex-col gap-6 border-b border-border pb-8 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-2">
                            <Link
                                :href="route('frontend.orders')"
                                class="group flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-primary"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                <span>{{ t("labels.actions.back_to_orders") }}</span>
                            </Link>
                            <h1 class="text-2xl font-bold text-foreground">{{ t("labels.order.order_number", { id: order.code }) }}</h1>
                            <p class="text-sm text-muted-foreground">{{ formatDate(order.created_at, dateTimeFormat) }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="rounded-full border px-6 py-2 text-sm font-semibold" :class="getOrderStatusColor(order.status)">
                                {{ getOrderStatusLabel(order.status, t) }}
                            </span>
                        </div>
                    </div>



                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                        <!-- Items & Shipping -->
                        <div class="space-y-6 lg:col-span-8">
                            <!-- Delivery (Kurir Toko) -->
                            <Card as="section" v-if="hasDelivery" class="space-y-4 rounded-xl p-6 md:p-8">
                                <div class="flex items-center gap-3 text-primary">
                                    <Truck class="h-5 w-5" />
                                    <h2 class="text-sm font-bold text-foreground">{{ t("labels.order.shipping_details") }}</h2>
                                </div>

                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm font-semibold text-foreground">
                                            <MapPin class="h-4 w-4 text-primary" />
                                            <span>{{ t("labels.order.shipping_address") }}</span>
                                        </div>
                                        <div class="space-y-1 text-sm leading-relaxed text-muted-foreground">
                                            <p class="font-semibold text-foreground">{{ order.address?.name || "Alamat Pengiriman" }}</p>
                                            <p>{{ order.address?.phone || "" }}</p>
                                            <p>{{ order.address?.full_address }}</p>
                                            <p>{{ order.address?.village }}, {{ order.address?.sub_district }}, {{ order.address?.district }}</p>
                                            <p>{{ order.address?.province }} {{ order.address?.postal_code }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm font-semibold text-foreground">
                                            <Package class="h-4 w-4 text-primary" />
                                            <span>{{ t("labels.order.shipping_method") }}</span>
                                        </div>

                                    <div class="space-y-3">
                                            <div v-if="order.receipt_code" class="text-sm text-muted-foreground">
                                                <span class="font-semibold text-foreground">{{ t('labels.order.tracking_number') }}:</span>
                                                {{ order.receipt_code }}
                                            </div>

                                            <div v-if="deliveryCouriers" class="text-sm text-muted-foreground">
                                                <span class="font-semibold text-foreground">{{ t('labels.order.shipping_method') }}:</span>
                                                {{ deliveryCouriers }}
                                            </div>

                                            <div v-for="group in deliveryGroups" :key="group.warehouse_id" class="rounded-lg border border-border bg-secondary p-4">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-bold text-foreground">{{ group.warehouse_name }}</p>
                                                        <p class="mt-1 text-xs text-muted-foreground">
                                                            {{ group.courier_name || group.courier_code }}
                                                            <span v-if="group.estimation">• {{ group.estimation }}</span>
                                                        </p>
                                                        <p v-if="group.warehouse_contact_phone || group.warehouse_contact_name" class="mt-2 text-xs text-muted-foreground">
                                                            <span class="font-semibold text-foreground">{{ t('labels.order.warehouse_contact') }}:</span>
                                                            <span v-if="group.warehouse_contact_name">{{ group.warehouse_contact_name }}</span>
                                                            <span v-if="group.warehouse_contact_name && group.warehouse_contact_phone"> • </span>
                                                            <span v-if="group.warehouse_contact_phone">{{ formatPhone(group.warehouse_contact_phone) }}</span>
                                                        </p>
                                                    </div>
                                                    <p class="text-sm font-bold text-primary">{{ formatCurrency(group.price || 0) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="order.notes" class="rounded-lg bg-primary/10 p-4 text-sm text-muted-foreground">
                                    <p class="font-semibold text-foreground">{{ t('labels.order.order_notes') }}</p>
                                    <p class="mt-1 whitespace-pre-line">{{ order.notes }}</p>
                                </div>

                                <div v-if="statusDates.length" class="rounded-lg border border-border bg-background p-4">
                                    <p class="text-sm font-bold text-foreground">{{ t('labels.order.status_dates.title') }}</p>
                                    <div class="mt-3 space-y-2 text-sm text-muted-foreground">
                                        <div v-for="item in statusDates" :key="item.key" class="flex justify-between gap-4">
                                            <span class="font-medium text-foreground">{{ getStatusDateLabel(item.key) }}</span>
                                            <span>{{ formatDate(item.date, dateTimeFormat) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </Card>

                            <!-- Pickup Locations (if has pickup) -->
                            <section v-if="hasPickup" class="rounded-xl border border-border bg-background p-6 shadow-sm md:p-8">
                                <h2 class="mb-6 border-b border-border pb-4 text-base font-bold text-foreground flex items-center gap-2">
                                    <Store class="h-5 w-5 text-primary" />
                                    {{ t('labels.order.pickup_address') }}
                                </h2>
                                <div class="space-y-4">
                                    <div
                                        v-for="group in pickupGroups"
                                        :key="group.warehouse_id"
                                        class="rounded-lg bg-primary/10 p-4"
                                    >
                                        <div class="mb-2 text-sm font-semibold text-primary">
                                            📍 {{ group.warehouse_name }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">{{ group.warehouse_address }}</div>
                                        <div v-if="group.courier_name || group.courier_code" class="mt-2 text-xs text-muted-foreground">
                                            <span class="font-semibold text-foreground">{{ t('labels.order.pickup_method') }}:</span>
                                            {{ group.courier_name || group.courier_code }}
                                        </div>
                                        <div v-if="group.warehouse_contact_phone || group.warehouse_contact_name" class="mt-2 text-xs text-muted-foreground">
                                            <span class="font-semibold text-foreground">{{ t('labels.order.warehouse_contact') }}:</span>
                                            <span v-if="group.warehouse_contact_name">{{ group.warehouse_contact_name }}</span>
                                            <span v-if="group.warehouse_contact_name && group.warehouse_contact_phone"> • </span>
                                            <span v-if="group.warehouse_contact_phone">{{ formatPhone(group.warehouse_contact_phone) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Items Section -->
                            <section class="rounded-xl border border-border bg-background p-6 shadow-sm md:p-8">
                                <h2 class="mb-6 border-b border-border pb-4 text-base font-bold text-foreground">{{ t("labels.order.items_title") }}</h2>
                                <div class="space-y-6">
                                    <div v-for="group in getGroupedProducts()" :key="group.warehouse_id" class="space-y-4">
                                        <!-- Warehouse Label -->
                                        <div v-if="order.shipping_groups?.length > 1" class="flex items-center gap-2 text-xs font-semibold text-muted-foreground">
                                            <span>{{ group.is_pickup ? '📦' : '🚚' }}</span>
                                            <span>{{ group.warehouse_name }}</span>
                                        </div>
                                        <!-- Products -->
                                        <div v-for="item in group.products" :key="item.id" class="flex gap-5">
                                            <div class="h-24 w-20 flex-shrink-0 overflow-hidden rounded-xl bg-secondary">
                                                <img
                                                    :src="item.product_thumbnail || item.product?.thumbnail"
                                                    :alt="item.product_name || item.product?.name || 'Produk'"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div class="flex flex-grow flex-col py-1">
                                                <div class="flex justify-between">
                                                    <div>
                                                        <h4 class="text-sm font-bold text-foreground">{{ item.product_name || item.product?.name }}</h4>
                                                        <p v-if="item.variant_name || item.description" class="mt-1 text-xs text-muted-foreground">
                                                            {{ item.variant_name || item.description }}
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <p class="text-sm font-bold text-primary">{{ formatCurrency(item.final_price || item.price) }}</p>
                                                        <p v-if="item.price > (item.final_price || item.price)" class="text-xs text-muted-foreground line-through">
                                                            {{ formatCurrency(item.price) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="mt-auto flex items-center justify-between text-sm text-muted-foreground">
                                                    <span>{{ t("labels.order.quantity", { qty: item.quantity }) }}</span>
                                                    <span class="font-semibold text-foreground"
                                                        >{{ t("labels.order.line_total", { amount: formatCurrency(item.total) }) }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    v-if="order.status === 'completed' && reviewableProducts.length"
                                    type="button"
                                    @click="openReviewDialog"
                                    class="mt-8 w-full rounded-xl bg-primary py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 active:scale-[0.99]"
                                >
                                    {{ t('labels.order.review.rate') }}
                                </Button>
                                <p v-else-if="order.status === 'completed'" class="mt-8 text-center text-sm font-medium text-primary">
                                    {{ t('labels.order.review.all_submitted') }}
                                </p>
                            </section>
                        </div>

                        <!-- Payment & Totals -->
                        <div class="space-y-6 lg:col-span-4">
                            <!-- Payment Section -->
                            <section class="space-y-5 rounded-xl border border-border bg-background p-6 shadow-sm md:p-8">
                                <div class="flex items-center gap-3 border-b border-border pb-4 text-primary">
                                    <CreditCard class="h-5 w-5" />
                                    <h2 class="text-sm font-bold text-foreground">{{ t("labels.order.payment") }}</h2>
                                </div>
                                <div class="space-y-3 text-sm text-muted-foreground">
                                    <div class="flex justify-between">
                                        <span>{{ t('labels.order.payment_method') }}</span>
                                        <span class="font-semibold text-foreground">{{ getPaymentLabel() }}</span>
                                    </div>
                                    <div v-if="order.installment_plan" class="flex justify-between">
                                        <span>{{ t('labels.order.monthly_amount') }}</span>
                                        <span class="font-semibold text-foreground">{{ formatCurrency(order.installment_plan.monthly_amount) }}</span>
                                    </div>
                                </div>
                                <div v-if="order.status === 'packed'" class="mt-4 pt-4 border-t border-border">
                                    <Button
                                        @click="cancelOrder"
                                        class="w-full rounded-xl border border-destructive/30 bg-destructive/10 py-3 text-sm font-semibold text-destructive transition-all hover:bg-destructive/15 hover:text-destructive active:scale-[0.98]"
                                    >
                                        Batalkan Pesanan
                                    </Button>
                                </div>
                            </section>

                            <!-- Totals Section -->
                            <section class="space-y-5 rounded-xl bg-foreground p-6 text-primary-foreground shadow-lg md:p-8">
                                <h2 class="border-b border-primary-foreground/10 pb-4 text-sm font-bold text-primary-foreground/70">{{ t("labels.order.summary_title") }}</h2>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-primary-foreground/70">{{ t("labels.order.subtotal") }}</span>
                                        <span class="font-semibold">{{ formatCurrency(order.subtotal) }}</span>
                                    </div>
                                    <div v-if="order.product_discount > 0" class="flex justify-between text-sm">
                                        <span class="text-primary-foreground/70">{{ t("labels.order.product_discount") }}</span>
                                        <span class="font-semibold text-primary">-{{ formatCurrency(order.product_discount) }}</span>
                                    </div>
                                    <div v-if="order.voucher_discount > 0" class="flex justify-between text-sm">
                                        <span class="text-primary-foreground/70">{{ t("labels.order.voucher_discount") }}</span>
                                        <span class="font-semibold text-primary">-{{ formatCurrency(order.voucher_discount) }}</span>
                                    </div>
                                    <div v-if="order.total_discount > 0" class="flex justify-between text-sm">
                                        <span class="text-primary-foreground/70">{{ t("labels.order.total_discount") }}</span>
                                        <span class="font-semibold text-primary">-{{ formatCurrency(order.total_discount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-primary-foreground/70">{{ t("labels.order.shipping") }}</span>
                                        <span class="font-semibold">{{ formatCurrency(order.shipping_cost) }}</span>
                                    </div>
                                    <div v-if="order.cod_fee > 0" class="flex justify-between text-sm">
                                        <span class="text-primary-foreground/70">COD Fee</span>
                                        <span class="font-semibold">{{ formatCurrency(order.cod_fee) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-primary-foreground/20 pt-4">
                                        <span class="text-sm font-bold">{{ t("labels.order.total") }}</span>
                                        <span class="text-xl font-bold text-primary">{{ formatCurrency(order.total) }}</span>
                                    </div>
                                </div>
                            </section>

                            <section v-if="order.vouchers?.length" class="space-y-4 rounded-xl border border-border bg-background p-6 shadow-sm md:p-8">
                                <h2 class="text-sm font-bold text-foreground">{{ t('labels.order.vouchers_title') }}</h2>
                                <div class="space-y-3">
                                    <div v-for="voucher in order.vouchers" :key="voucher.voucher_code" class="rounded-lg border border-border bg-secondary p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-foreground">{{ voucher.voucher_name }}</p>
                                                <p class="mt-1 text-xs text-muted-foreground">{{ voucher.voucher_code }}</p>
                                            </div>
                                            <p class="text-sm font-bold text-primary">-{{ formatCurrency(voucher.discount_amount || 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </PageShell>

        <div v-if="reviewDialogOpen" class="fixed inset-0 z-50 flex items-end bg-foreground/50 p-0 sm:items-center sm:p-6" @click.self="reviewDialogOpen = false">
            <form @submit.prevent="submitReviews" class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-background p-6 shadow-2xl sm:mx-auto sm:max-w-2xl sm:rounded-2xl md:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-border pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-foreground">{{ t('labels.order.review.title') }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ t('labels.order.review.description') }}</p>
                    </div>
                    <Button type="button" @click="reviewDialogOpen = false" :aria-label="t('labels.order.review.close')" class="rounded-lg p-2 text-muted-foreground hover:bg-secondary">
                        <X class="h-5 w-5" />
                    </Button>
                </div>

                <div class="divide-y divide-border">
                    <section v-for="item in reviewableProducts" :key="item.id" class="space-y-4 py-6">
                        <div class="flex items-center gap-4">
                            <img :src="item.product_thumbnail || item.product?.thumbnail" :alt="item.product_name || item.product?.name" class="h-16 w-16 rounded-lg bg-secondary object-cover" />
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-foreground">{{ item.product_name || item.product?.name }}</h3>
                                <p v-if="item.variant_name || item.description" class="mt-1 text-xs text-muted-foreground">{{ item.variant_name || item.description }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-semibold text-foreground">{{ t('labels.order.review.product_rating') }}</p>
                            <div class="flex items-center gap-1">
                                <Button v-for="star in 5" :key="star" type="button" @click="reviewForm(item).rating = star" class="p-0.5" :aria-label="t('labels.product.stars', { rating: star })">
                                    <Star class="h-7 w-7" :class="star <= reviewForm(item).rating ? 'fill-accent text-accent' : 'text-muted-foreground/50'" />
                                </Button>
                            </div>
                        </div>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-foreground">{{ t('labels.order.review.upload_photo') }}</span>
                            <FormFile accept="image/*" multiple @change="reviewForm(item).images = Array.from($event.target.files).slice(0, 3)" class="text-xs text-muted-foreground" />
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-foreground">{{ t('labels.order.review.experience') }}</span>
                            <FormTextarea v-model="reviewForm(item).review" maxlength="7000" rows="3" :placeholder="t('labels.order.review.experience_placeholder')" class="border-primary px-3 py-2 leading-relaxed placeholder:text-muted-foreground" />
                        </label>
                        <label class="flex items-center gap-2 text-sm text-muted-foreground"><FormCheckbox v-model="reviewForm(item).is_anonymous" class="border-border text-primary" /> {{ t('labels.order.review.anonymous') }}</label>
                    </section>
                </div>

                <Button type="submit" class="mt-2 w-full rounded-xl bg-primary py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 active:scale-[0.99]">
                    {{ t('labels.order.review.submit') }}
                </Button>
            </form>
        </div>

    </TemplateWrapper>
</template>
