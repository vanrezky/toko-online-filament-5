<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import { useI18n } from "vue-i18n";
import { Package, ChevronRight, ChevronLeft } from "lucide-vue-next";
import { formatCurrency, formatDate } from "../../lib/utils";
import Card from "../../components/UI/Card.vue";
import { ORDER_STATUS_FILTERS, getOrderStatusColor, getOrderStatusLabel } from "../../lib/order-status";

const props = defineProps({
    orders: Object,
    activeStatus: String,
});
const { t } = useI18n();

const activeTab = ref(props.activeStatus || "all");
const items = ref([]);
const isLoadingMore = ref(false);

const tabs = ORDER_STATUS_FILTERS;

const nextPageUrl = computed(() => props.orders?.links?.next || null);
const currentPage = computed(() => props.orders?.meta?.current_page || 1);

const syncItemsFromProps = () => {
    const newItems = props.orders?.data || [];

    if (currentPage.value <= 1) {
        items.value = newItems;
        return;
    }

    const existingIds = new Set(items.value.map((o) => o.id));
    for (const order of newItems) {
        if (!existingIds.has(order.id)) items.value.push(order);
    }
};

watch(
    () => props.activeStatus,
    (status) => {
        activeTab.value = status || "all";
    },
);

watch(
    () => props.orders,
    () => syncItemsFromProps(),
    { deep: true, immediate: true },
);

const switchTab = (tab) => {
    if (isLoadingMore.value) return;
    if (activeTab.value === tab && currentPage.value === 1) return;

    activeTab.value = tab;
    router.get(
        route("frontend.orders"),
        { status: tab, per_page: 10 },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ["orders", "activeStatus"],
        },
    );
};

const loadMore = () => {
    if (isLoadingMore.value) return;
    if (!nextPageUrl.value) return;

    isLoadingMore.value = true;
    router.get(
        nextPageUrl.value,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ["orders"],
            onFinish: () => {
                isLoadingMore.value = false;
            },
        },
    );
};

const onScroll = () => {
    const distanceFromBottom = document.documentElement.scrollHeight - (window.scrollY + window.innerHeight);
    if (distanceFromBottom < 200) loadMore();
};

onMounted(() => window.addEventListener("scroll", onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener("scroll", onScroll));

const dateFormat = { year: "numeric", month: "short", day: "numeric" };

const isExpired = (dateString) => {
    return new Date(dateString) < new Date();
};
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('meta.orders.title')">
        <PageShell>
            <div class="container mx-auto px-4 md:px-6">
                <div class="mx-auto max-w-4xl space-y-8">
                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div class="space-y-2">
                            <Link
                                :href="route('frontend.account')"
                                class="group flex w-fit items-center gap-2 text-sm text-[#6b5a4d] transition-colors hover:text-[#fa8456]"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                <span>kembali</span>
                            </Link>
                            <h1 class="text-3xl font-bold text-[#2d1b0e]">{{ t("labels.orders.heading") }}</h1>
                        </div>
                    </div>

                    <!-- Tabs (always visible) -->
                    <div class="scrollbar-hidden -mx-4 touch-pan-x overflow-x-auto overscroll-x-contain px-4 md:mx-0 md:px-0">
                        <div class="flex w-max items-center gap-1 pb-1 sm:gap-2">
                            <Button
                                v-for="tab in tabs"
                                :key="tab"
                                type="button"
                                class="inline-flex items-center gap-2 whitespace-nowrap rounded-full border px-4 py-2 text-xs font-semibold transition-all"
                                :class="
                                    activeTab === tab
                                        ? 'border-[#fa8456] bg-[#fff5f0] text-[#fa8456]'
                                        : 'border-[#e8e6ef] bg-white text-[#6b5a4d] hover:bg-[#f5f3fc]'
                                "
                                @click="switchTab(tab)"
                            >
                                <span>{{ getOrderStatusLabel(tab, t) }}</span>
                            </Button>
                        </div>
                    </div>

                    <div v-if="items && items.length > 0" class="space-y-5">
                        <Link
                            v-for="order in items"
                            :key="order.id"
                            :href="route('frontend.orders.show', order.id)"
                            class="group block space-y-5 rounded-xl border border-[#e8e6ef] bg-white p-5 shadow-sm transition-all hover:shadow-md"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#fff5f0]">
                                        <Package class="h-5 w-5 text-[#fa8456]" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-[#2d1b0e]">{{ t("labels.order.order_number", { id: order.code }) }}</h3>
                                        <p class="mt-0.5 text-xs text-[#6b5a4d]">{{ formatDate(order.created_at, dateFormat) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span
                                        class="rounded-full px-4 py-1.5 text-xs font-semibold"
                                        :class="getOrderStatusColor(order.status, 'bg-[#f5f3fc] text-[#6b5a4d]')"
                                    >
                                        {{ getOrderStatusLabel(order.status, t) }}
                                    </span>
                                    <p class="text-sm font-bold text-[#fa8456]">{{ formatCurrency(order.total) }}</p>
                                    <ChevronRight class="h-5 w-5 text-[#6b5a4d] transition-colors group-hover:text-[#fa8456]" />
                                </div>
                            </div>

                            <!-- Timelimit Warning -->
                            <!-- Preview Images -->
                            <div class="flex gap-3 overflow-x-auto border-t border-[#f0eef5] pt-5">
                                <div v-for="item in order.products" :key="item.uuid" class="group/item relative flex-shrink-0">
                                    <div class="h-16 w-16 overflow-hidden rounded-lg bg-[#f5f3fc] sm:h-20 sm:w-20">
                                        <img
                                            :src="item.product_thumbnail || item.product?.thumbnail"
                                            :alt="item.product_name || item.product?.name || 'Produk'"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <span
                                        class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-[#fa8456] text-[10px] font-bold text-white"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                </div>
                            </div>
                        </Link>

                        <div v-if="isLoadingMore" class="py-4 text-center text-sm text-[#6b5a4d]">
                            {{ t("labels.actions.loading") || "Memuat..." }}
                        </div>
                    </div>

                    <!-- Empty State -->
                    <Card v-else class="space-y-6 border-0 py-16 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[#f5f3fc]">
                            <Package class="h-10 w-10 text-[#c4bfc9]" />
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-base font-bold text-[#2d1b0e]">{{ t("labels.orders.none") }}</h2>
                        </div>
                    </Card>
                </div>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
