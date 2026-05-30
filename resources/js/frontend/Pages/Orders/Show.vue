<script setup>
import { computed, getCurrentInstance } from "vue";
import { Link, router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import { useI18n } from "vue-i18n";
import { Package, ChevronLeft, MapPin, Truck, CreditCard, CheckCircle2, Store } from "lucide-vue-next";

const props = defineProps({
    order: Object,
});
const { t } = useI18n();
const { proxy } = getCurrentInstance();

const statusColors = {
    packed: "text-[#6366f1] bg-[#eef2ff] border-[#c7d2fe]",
    in_transit: "text-[#0ea5e9] bg-[#ecfeff] border-[#a5f3fc]",
    shipped: "text-[#3b82f6] bg-[#eff6ff] border-[#bfdbfe]",
    picked_up: "text-[#14b8a6] bg-[#f0fdfa] border-[#99f6e4]",
    delivered: "text-[#22c55e] bg-[#f0fdf4] border-[#bbf7d0]",
    completed: "text-[#16a34a] bg-[#dcfce7] border-[#86efac]",
    cancelled: "text-gray-500 bg-gray-50 border-gray-200",
};

const statusLabels = computed(() => ({
    packed: t("labels.order.status.packed"),
    in_transit: t("labels.order.status.in_transit"),
    shipped: t("labels.order.status.shipped"),
    picked_up: t("labels.order.status.picked_up"),
    delivered: t("labels.order.status.delivered"),
    completed: t("labels.order.status.completed"),
    cancelled: t("labels.order.status.cancelled") || "Dibatalkan",
}));

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

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getStatusDateLabel = (status) => {
    if (status === "cancelled") return t("labels.order.status_dates.cancelled");
    if (status === "completed") return t("labels.order.status_dates.completed");
    return t("labels.order.status_dates.shipped");
};

const formatPhone = (phone) => {
    if (!phone) return "";
    return String(phone).replace(/\s+/g, " ").trim();
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
    <TemplateWrapper :title="t('meta.order_detail.title')">
        <div class="min-h-screen bg-[#f8f7fc] py-12 font-sans md:py-20">
            <div class="container mx-auto px-4 md:px-6">
                <div class="mx-auto max-w-5xl space-y-8">
                    <!-- Header -->
                    <div class="flex flex-col gap-6 border-b border-[#e8e6ef] pb-8 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-2">
                            <Link
                                :href="route('frontend.orders')"
                                class="group flex items-center gap-2 text-sm text-[#6b5a4d] transition-colors hover:text-[#fa8456]"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                <span>{{ t("labels.actions.back_to_orders") }}</span>
                            </Link>
                            <h1 class="text-2xl font-bold text-[#2d1b0e]">{{ t("labels.order.order_number", { id: order.code }) }}</h1>
                            <p class="text-sm text-[#6b5a4d]">{{ formatDate(order.created_at) }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="rounded-full border px-6 py-2 text-sm font-semibold" :class="statusColors[order.status]">
                                {{ statusLabels[order.status] || order.status }}
                            </span>
                        </div>
                    </div>



                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                        <!-- Items & Shipping -->
                        <div class="space-y-6 lg:col-span-8">
                            <!-- Delivery (Kurir Toko) -->
                            <section v-if="hasDelivery" class="space-y-4 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                                <div class="flex items-center gap-3 text-[#fa8456]">
                                    <Truck class="h-5 w-5" />
                                    <h2 class="text-sm font-bold text-[#2d1b0e]">{{ t("labels.order.shipping_details") }}</h2>
                                </div>

                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm font-semibold text-[#2d1b0e]">
                                            <MapPin class="h-4 w-4 text-[#fa8456]" />
                                            <span>{{ t("labels.order.shipping_address") }}</span>
                                        </div>
                                        <div class="space-y-1 text-sm leading-relaxed text-[#6b5a4d]">
                                            <p class="font-semibold text-[#2d1b0e]">{{ order.address?.name || "Alamat Pengiriman" }}</p>
                                            <p>{{ order.address?.phone || "" }}</p>
                                            <p>{{ order.address?.full_address }}</p>
                                            <p>{{ order.address?.village }}, {{ order.address?.sub_district }}, {{ order.address?.district }}</p>
                                            <p>{{ order.address?.province }} {{ order.address?.postal_code }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-sm font-semibold text-[#2d1b0e]">
                                            <Package class="h-4 w-4 text-[#fa8456]" />
                                            <span>{{ t("labels.order.shipping_method") }}</span>
                                        </div>

                                    <div class="space-y-3">
                                            <div v-if="order.receipt_code" class="text-sm text-[#6b5a4d]">
                                                <span class="font-semibold text-[#2d1b0e]">{{ t('labels.order.tracking_number') }}:</span>
                                                {{ order.receipt_code }}
                                            </div>

                                            <div v-if="deliveryCouriers" class="text-sm text-[#6b5a4d]">
                                                <span class="font-semibold text-[#2d1b0e]">{{ t('labels.order.shipping_method') }}:</span>
                                                {{ deliveryCouriers }}
                                            </div>

                                            <div v-for="group in deliveryGroups" :key="group.warehouse_id" class="rounded-lg border border-[#f0eef5] bg-[#faf9fd] p-4">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-bold text-[#2d1b0e]">{{ group.warehouse_name }}</p>
                                                        <p class="mt-1 text-xs text-[#6b5a4d]">
                                                            {{ group.courier_name || group.courier_code }}
                                                            <span v-if="group.estimation">• {{ group.estimation }}</span>
                                                        </p>
                                                        <p v-if="group.warehouse_contact_phone || group.warehouse_contact_name" class="mt-2 text-xs text-[#6b5a4d]">
                                                            <span class="font-semibold text-[#2d1b0e]">{{ t('labels.order.warehouse_contact') }}:</span>
                                                            <span v-if="group.warehouse_contact_name">{{ group.warehouse_contact_name }}</span>
                                                            <span v-if="group.warehouse_contact_name && group.warehouse_contact_phone"> • </span>
                                                            <span v-if="group.warehouse_contact_phone">{{ formatPhone(group.warehouse_contact_phone) }}</span>
                                                        </p>
                                                    </div>
                                                    <p class="text-sm font-bold text-[#fa8456]">{{ formatCurrency(group.price || 0) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="order.notes" class="rounded-lg bg-[#fff5f0] p-4 text-sm text-[#6b5a4d]">
                                    <p class="font-semibold text-[#2d1b0e]">{{ t('labels.order.order_notes') }}</p>
                                    <p class="mt-1 whitespace-pre-line">{{ order.notes }}</p>
                                </div>

                                <div v-if="statusDates.length" class="rounded-lg border border-[#f0eef5] bg-white p-4">
                                    <p class="text-sm font-bold text-[#2d1b0e]">{{ t('labels.order.status_dates.title') }}</p>
                                    <div class="mt-3 space-y-2 text-sm text-[#6b5a4d]">
                                        <div v-for="item in statusDates" :key="item.key" class="flex justify-between gap-4">
                                            <span class="font-medium text-[#2d1b0e]">{{ getStatusDateLabel(item.key) }}</span>
                                            <span>{{ formatDate(item.date) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Pickup Locations (if has pickup) -->
                            <section v-if="hasPickup" class="rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                                <h2 class="mb-6 border-b border-[#f0eef5] pb-4 text-base font-bold text-[#2d1b0e] flex items-center gap-2">
                                    <Store class="h-5 w-5 text-[#fa8456]" />
                                    {{ t('labels.order.pickup_address') }}
                                </h2>
                                <div class="space-y-4">
                                    <div
                                        v-for="group in pickupGroups"
                                        :key="group.warehouse_id"
                                        class="rounded-lg bg-[#fff5f0] p-4"
                                    >
                                        <div class="mb-2 text-sm font-semibold text-[#fa8456]">
                                            📍 {{ group.warehouse_name }}
                                        </div>
                                        <div class="text-sm text-[#6b5a4d]">{{ group.warehouse_address }}</div>
                                        <div v-if="group.courier_name || group.courier_code" class="mt-2 text-xs text-[#6b5a4d]">
                                            <span class="font-semibold text-[#2d1b0e]">{{ t('labels.order.pickup_method') }}:</span>
                                            {{ group.courier_name || group.courier_code }}
                                        </div>
                                        <div v-if="group.warehouse_contact_phone || group.warehouse_contact_name" class="mt-2 text-xs text-[#6b5a4d]">
                                            <span class="font-semibold text-[#2d1b0e]">{{ t('labels.order.warehouse_contact') }}:</span>
                                            <span v-if="group.warehouse_contact_name">{{ group.warehouse_contact_name }}</span>
                                            <span v-if="group.warehouse_contact_name && group.warehouse_contact_phone"> • </span>
                                            <span v-if="group.warehouse_contact_phone">{{ formatPhone(group.warehouse_contact_phone) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Items Section -->
                            <section class="rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                                <h2 class="mb-6 border-b border-[#f0eef5] pb-4 text-base font-bold text-[#2d1b0e]">{{ t("labels.order.items_title") }}</h2>
                                <div class="space-y-6">
                                    <div v-for="group in getGroupedProducts()" :key="group.warehouse_id" class="space-y-4">
                                        <!-- Warehouse Label -->
                                        <div v-if="order.shipping_groups?.length > 1" class="flex items-center gap-2 text-xs font-semibold text-[#6b5a4d]">
                                            <span>{{ group.is_pickup ? '📦' : '🚚' }}</span>
                                            <span>{{ group.warehouse_name }}</span>
                                        </div>
                                        <!-- Products -->
                                        <div v-for="item in group.products" :key="item.id" class="flex gap-5">
                                            <div class="h-24 w-20 flex-shrink-0 overflow-hidden rounded-xl bg-[#f5f3fc]">
                                                <img
                                                    :src="item.product?.thumbnail || 'https://placehold.co/100x120/f5f3fc/2d1b0e?text=Produk'"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div class="flex flex-grow flex-col py-1">
                                                <div class="flex justify-between">
                                                    <div>
                                                        <h4 class="text-sm font-bold text-[#2d1b0e]">{{ item.product?.name }}</h4>
                                                        <p v-if="item.description" class="mt-1 text-xs text-[#6b5a4d]">{{ item.description }}</p>
                                                    </div>
                                                    <p class="text-sm font-bold text-[#fa8456]">{{ formatCurrency(item.price) }}</p>
                                                </div>
                                                <div class="mt-auto flex items-center justify-between text-sm text-[#6b5a4d]">
                                                    <span>{{ t("labels.order.quantity", { qty: item.quantity }) }}</span>
                                                    <span class="font-semibold text-[#2d1b0e]"
                                                        >{{ t("labels.order.line_total", { amount: formatCurrency(item.price * item.quantity) }) }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- Payment & Totals -->
                        <div class="space-y-6 lg:col-span-4">
                            <!-- Payment Section -->
                            <section class="space-y-5 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                                <div class="flex items-center gap-3 border-b border-[#f0eef5] pb-4 text-[#fa8456]">
                                    <CreditCard class="h-5 w-5" />
                                    <h2 class="text-sm font-bold text-[#2d1b0e]">{{ t("labels.order.payment") }}</h2>
                                </div>
                                <div class="space-y-3 text-sm text-[#6b5a4d]">
                                    <div class="flex justify-between">
                                        <span>{{ t('labels.order.payment_method') }}</span>
                                        <span class="font-semibold text-[#2d1b0e]">{{ getPaymentLabel() }}</span>
                                    </div>
                                    <div v-if="order.installment_plan" class="flex justify-between">
                                        <span>{{ t('labels.order.monthly_amount') }}</span>
                                        <span class="font-semibold text-[#2d1b0e]">{{ formatCurrency(order.installment_plan.monthly_amount) }}</span>
                                    </div>
                                </div>
                                <div v-if="order.status === 'packed'" class="mt-4 pt-4 border-t border-[#f0eef5]">
                                    <button
                                        @click="cancelOrder"
                                        class="w-full rounded-xl border border-red-200 bg-red-50/50 py-3 text-sm font-semibold text-red-600 transition-all hover:bg-red-50 hover:text-red-700 active:scale-[0.98]"
                                    >
                                        Batalkan Pesanan
                                    </button>
                                </div>
                            </section>

                            <!-- Totals Section -->
                            <section class="space-y-5 rounded-xl bg-[#2d1b0e] p-6 text-white shadow-lg md:p-8">
                                <h2 class="border-b border-white/10 pb-4 text-sm font-bold text-[#c4bfc9]">{{ t("labels.order.summary_title") }}</h2>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-[#c4bfc9]">{{ t("labels.order.subtotal") }}</span>
                                        <span class="font-semibold">{{ formatCurrency(order.subtotal) }}</span>
                                    </div>
                                    <div v-if="order.product_discount > 0" class="flex justify-between text-sm">
                                        <span class="text-[#c4bfc9]">{{ t("labels.order.product_discount") }}</span>
                                        <span class="font-semibold text-[#fa8456]">-{{ formatCurrency(order.product_discount) }}</span>
                                    </div>
                                    <div v-if="order.voucher_discount > 0" class="flex justify-between text-sm">
                                        <span class="text-[#c4bfc9]">{{ t("labels.order.voucher_discount") }}</span>
                                        <span class="font-semibold text-[#fa8456]">-{{ formatCurrency(order.voucher_discount) }}</span>
                                    </div>
                                    <div v-if="order.total_discount > 0" class="flex justify-between text-sm">
                                        <span class="text-[#c4bfc9]">{{ t("labels.order.total_discount") }}</span>
                                        <span class="font-semibold text-[#fa8456]">-{{ formatCurrency(order.total_discount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-[#c4bfc9]">{{ t("labels.order.shipping") }}</span>
                                        <span class="font-semibold">{{ formatCurrency(order.shipping_cost) }}</span>
                                    </div>
                                    <div v-if="order.cod_fee > 0" class="flex justify-between text-sm">
                                        <span class="text-[#c4bfc9]">COD Fee</span>
                                        <span class="font-semibold">{{ formatCurrency(order.cod_fee) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-white/20 pt-4">
                                        <span class="text-sm font-bold">{{ t("labels.order.total") }}</span>
                                        <span class="text-xl font-bold text-[#fa8456]">{{ formatCurrency(order.total) }}</span>
                                    </div>
                                </div>
                            </section>

                            <section v-if="order.vouchers?.length" class="space-y-4 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                                <h2 class="text-sm font-bold text-[#2d1b0e]">{{ t('labels.order.vouchers_title') }}</h2>
                                <div class="space-y-3">
                                    <div v-for="voucher in order.vouchers" :key="voucher.voucher_code" class="rounded-lg border border-[#f0eef5] bg-[#faf9fd] p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-[#2d1b0e]">{{ voucher.voucher_name }}</p>
                                                <p class="mt-1 text-xs text-[#6b5a4d]">{{ voucher.voucher_code }}</p>
                                            </div>
                                            <p class="text-sm font-bold text-[#fa8456]">-{{ formatCurrency(voucher.discount_amount || 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </TemplateWrapper>
</template>
