<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import { useI18n } from "vue-i18n";
import { Package, ChevronLeft, MapPin, Truck, CreditCard, CheckCircle2, Store } from "lucide-vue-next";

const props = defineProps({
    order: Object,
});
const { t } = useI18n();

const statusColors = {
    unpaid: "text-[#fa8456] bg-[#fff5f0] border-[#fed7aa]",
    shipped: "text-[#3b82f6] bg-[#eff6ff] border-[#bfdbfe]",
    delivered: "text-[#22c55e] bg-[#f0fdf4] border-[#bbf7d0]",
    completed: "text-[#16a34a] bg-[#dcfce7] border-[#86efac]",
    rejected: "text-[#ef4444] bg-[#fef2f2] border-[#fecaca]",
};

const statusLabels = computed(() => ({
    unpaid: t("labels.order.status.unpaid"),
    shipped: t("labels.order.status.shipped"),
    delivered: t("labels.order.status.delivered"),
    completed: t("labels.order.status.completed"),
    rejected: t("labels.order.status.rejected"),
}));

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

const subtotal = computed(() => {
    return props.order.products?.reduce((acc, p) => acc + p.price * p.quantity, 0) || 0;
});

const hasPickup = computed(() => {
    return props.order.shipping_groups?.some(group => group.is_pickup);
});

const hasDelivery = computed(() => {
    return props.order.shipping_groups?.some(group => !group.is_pickup);
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
                            <h1 class="text-2xl font-bold text-[#2d1b0e]">{{ t("labels.order.order_number", { id: order.id.substring(0, 8).toUpperCase() }) }}</h1>
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
                            <!-- Pickup Locations (if has pickup) -->
                            <section v-if="hasPickup" class="rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                                <h2 class="mb-6 border-b border-[#f0eef5] pb-4 text-base font-bold text-[#2d1b0e] flex items-center gap-2">
                                    <Store class="h-5 w-5 text-[#fa8456]" />
                                    {{ t('labels.orders.pickup_location') }}
                                </h2>
                                <div class="space-y-4">
                                    <div
                                        v-for="group in order.shipping_groups?.filter(g => g.is_pickup)"
                                        :key="group.warehouse_id"
                                        class="rounded-lg bg-[#fff5f0] p-4"
                                    >
                                        <div class="mb-2 text-sm font-semibold text-[#fa8456]">
                                            📦 {{ group.warehouse_name }}
                                        </div>
                                        <div class="text-sm text-[#6b5a4d]">{{ group.warehouse_address }}</div>
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

                            <!-- Shipping Address (only if has delivery) -->
                            <section v-if="hasDelivery" class="space-y-4 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm">
                                <div class="flex items-center gap-3 text-[#fa8456]">
                                    <MapPin class="h-5 w-5" />
                                    <h2 class="text-sm font-bold text-[#2d1b0e]">{{ t("labels.order.shipping_address") }}</h2>
                                </div>
                                <div class="space-y-1 text-sm leading-relaxed text-[#6b5a4d]">
                                    <p class="font-semibold text-[#2d1b0e]">{{ order.address.name || "Alamat Pengiriman" }}</p>
                                    <p>{{ order.address.phone || "" }}</p>
                                    <p>{{ order.address.full_address }}</p>
                                    <p>{{ order.address.sub_district }}, {{ order.address.district }}</p>
                                    <p>{{ order.address.province }} {{ order.address.postal_code }}</p>
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
                                    <div class="flex justify-between">
                                        <span>{{ t("labels.order.payment_status_label") }}</span>
                                        <span class="font-semibold" :class="order.status === 'unpaid' ? 'text-[#fa8456]' : 'text-[#22c55e]'">
                                            {{ order.status === "unpaid" ? t("labels.order.status.unpaid") : t("labels.order.status.paid") }}
                                        </span>
                                    </div>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </TemplateWrapper>
</template>
