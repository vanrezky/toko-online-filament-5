<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { computed, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import { Trash2, ShoppingBag, ArrowRight, Minus, Plus } from "lucide-vue-next";
import { formatCurrency } from "../../lib/utils";
import Card from "../../components/UI/Card.vue";
import FormCheckbox from "../../components/UI/FormCheckbox.vue";
import debounce from "lodash/debounce";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    cart: Object,
});

const localItems = ref([...(props.cart?.items || [])]);
const selectedItemIds = ref(localItems.value.map((item) => item.id));

watch(
    () => props.cart?.items,
    (newItems) => {
        localItems.value = [...(newItems || [])];
        selectedItemIds.value = selectedItemIds.value.filter((id) => localItems.value.some((item) => item.id === id));
    },
    { deep: true },
);

const subtotal = computed(() => {
    return selectedItems.value.reduce((total, item) => total + item.price * item.quantity, 0);
});
const selectedItems = computed(() => localItems.value.filter((item) => selectedItemIds.value.includes(item.id)));
const isAllSelected = computed(() => localItems.value.length > 0 && selectedItemIds.value.length === localItems.value.length);

const toggleItemSelection = (itemId) => {
    selectedItemIds.value = selectedItemIds.value.includes(itemId)
        ? selectedItemIds.value.filter((id) => id !== itemId)
        : [...selectedItemIds.value, itemId];
};

const toggleAllSelection = () => {
    selectedItemIds.value = isAllSelected.value ? [] : localItems.value.map((item) => item.id);
};

const checkoutSelected = () => {
    if (selectedItemIds.value.length === 0) return;

    router.visit(route("frontend.checkout", { cart_item_ids: selectedItemIds.value }));
};

const updateQuantity = debounce((itemId, newQty) => {
    if (newQty < 1) return;

    router.patch(
        route("frontend.cart.update", itemId),
        {
            quantity: newQty,
        },
        {
            preserveScroll: true,
        },
    );
}, 300);

const handleQuantityChange = (item, delta) => {
    const newQty = item.quantity + delta;
    if (newQty < 1) return;

    item.quantity = newQty;
    updateQuantity(item.id, newQty);
};

const removeItem = (id) => {
    router.delete(route("frontend.cart.destroy", id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('labels.cart.heading')">
        <PageShell container :title="t('labels.cart.heading')">
            <div v-if="localItems.length > 0">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_380px]">
                    <!-- Cart Items List -->
                    <div class="space-y-4">
                        <label class="text-muted-foreground flex cursor-pointer items-center gap-2 px-1 text-sm font-semibold">
                            <FormCheckbox :model-value="isAllSelected" @update:model-value="toggleAllSelection" />
                            {{ t("labels.cart.select_all") }}
                        </label>
                        <Card v-for="item in localItems" :key="item.id" class="overflow-hidden rounded-2xl border-0 p-3 sm:p-4">
                            <label class="text-muted-foreground mb-3 flex cursor-pointer items-center gap-2 text-xs font-semibold">
                                <FormCheckbox :model-value="selectedItemIds.includes(item.id)" @update:model-value="toggleItemSelection(item.id)" />
                                {{ t("labels.cart.select_item") }}
                            </label>
                            <div class="grid grid-cols-[5rem_1fr] gap-3 sm:flex sm:gap-4">
                                <div class="bg-secondary h-20 w-20 shrink-0 overflow-hidden rounded-xl sm:h-32 sm:w-32">
                                    <Link :href="route('frontend.product-detail', item.product?.slug)">
                                        <img
                                            :src="item.product?.thumbnail || 'https://placehold.co/200x200?text=No+Image'"
                                            :alt="item.product?.name"
                                            class="h-full w-full object-cover transition-transform hover:scale-105"
                                        />
                                    </Link>
                                </div>

                                <div class="flex min-w-0 flex-grow flex-col justify-between">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-grow">
                                            <Link
                                                :href="route('frontend.product-detail', item.product?.slug)"
                                                class="text-foreground hover:text-primary line-clamp-2 text-sm leading-snug font-bold transition-colors sm:text-base"
                                            >
                                                {{ item.product?.name }}
                                            </Link>
                                            <p v-if="item.product_variant" class="text-muted-foreground mt-1 text-xs">
                                                {{ item.product_variant.variant_name }}
                                            </p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="text-primary text-sm font-bold sm:text-base">
                                                    {{ formatCurrency(item.price) }}
                                                </span>
                                                <span
                                                    v-if="item.original_price && item.original_price > item.price"
                                                    class="text-muted-foreground text-xs line-through"
                                                >
                                                    {{ formatCurrency(item.original_price) }}
                                                </span>
                                            </div>
                                        </div>
                                        <Button
                                            @click="removeItem(item.id)"
                                            class="text-muted-foreground flex shrink-0 items-center justify-center rounded-full p-2 transition-all hover:bg-red-50 hover:text-red-500"
                                        >
                                            <Trash2 class="h-5 w-5" />
                                        </Button>
                                    </div>

                                    <div class="col-span-2 mt-1 flex items-center justify-between gap-3 sm:mt-0">
                                        <div class="border-border bg-secondary/50 flex items-center rounded-full border">
                                            <Button
                                                @click="handleQuantityChange(item, -1)"
                                                class="text-muted-foreground hover:bg-secondary flex h-8 w-8 items-center justify-center rounded-l-full transition-all disabled:cursor-not-allowed disabled:opacity-50 sm:h-10 sm:w-10"
                                                :disabled="item.quantity <= 1"
                                            >
                                                <Minus class="h-4 w-4" />
                                            </Button>
                                            <span class="w-8 text-center text-sm font-semibold sm:w-14">{{ item.quantity }}</span>
                                            <Button
                                                @click="handleQuantityChange(item, 1)"
                                                class="text-muted-foreground hover:bg-secondary flex h-8 w-8 items-center justify-center rounded-r-full transition-all sm:h-10 sm:w-10"
                                            >
                                                <Plus class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <span class="text-foreground text-base font-bold sm:text-xl">
                                            {{ formatCurrency(item.price * item.quantity) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Order Summary Sidebar (Right) -->
                    <div>
                        <div class="sticky top-24 space-y-4">
                            <Card class="rounded-2xl border-0 p-6">
                                <h2 class="text-foreground mb-6 text-lg font-bold">{{ t("labels.cart.summary_title") }}</h2>

                                <div class="border-border space-y-4 border-b pb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">{{
                                            t("labels.cart.subtotal_with_count", { count: selectedItems.length })
                                        }}</span>
                                        <span class="text-foreground font-medium">{{ formatCurrency(subtotal) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">{{ t("labels.cart.shipping_cost") }}</span>
                                        <span class="text-foreground font-medium">{{ t("messages.info.shipping_calculated_at_checkout") }}</span>
                                    </div>
                                </div>

                                <div class="py-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <span class="text-foreground text-lg font-bold">{{ t("labels.cart.total") }}</span>
                                        <span class="text-primary text-2xl font-bold">{{ formatCurrency(subtotal) }}</span>
                                    </div>
                                </div>

                                <Button
                                    @click="checkoutSelected"
                                    :disabled="selectedItems.length === 0"
                                    class="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-full py-4 text-sm font-bold shadow-md transition-all hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span>{{ t("labels.cart.checkout_selected") }}</span>
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Card>
                        </div>
                    </div>
                </div>

                <Card variant="dashed" class="mt-4 rounded-2xl border-2 p-3 sm:p-4">
                    <div class="grid grid-cols-2 gap-3">
                        <Link
                            :href="route('frontend.products')"
                            class="text-muted-foreground hover:bg-secondary hover:text-primary flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-center text-sm font-semibold transition-colors"
                        >
                            <ShoppingBag class="h-4 w-4" />
                            {{ t("labels.actions.continue_shopping") }}
                        </Link>
                        <Link
                            :href="route('frontend.home')"
                            class="text-muted-foreground hover:bg-secondary hover:text-primary flex items-center justify-center rounded-lg px-3 py-2 text-center text-sm font-semibold transition-colors"
                        >
                            {{ t("labels.actions.home") }}
                        </Link>
                    </div>
                </Card>
            </div>
            <!-- Empty State -->
            <div v-else class="py-20 text-center">
                <div class="bg-secondary mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full">
                    <ShoppingBag class="text-muted-foreground h-12 w-12" />
                </div>
                <h2 class="text-foreground mb-2 text-2xl font-bold">{{ t("labels.cart.empty_title") }}</h2>
                <p class="text-muted-foreground mx-auto mb-8 max-w-md">{{ t("labels.cart.empty_description") }}</p>
                <Link
                    :href="route('frontend.products')"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-full px-8 py-4 text-sm font-bold shadow-md transition-all hover:shadow-lg"
                >
                    <ShoppingBag class="h-5 w-5" />
                    {{ t("labels.actions.start_shopping") }}
                </Link>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
