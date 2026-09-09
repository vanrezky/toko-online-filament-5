<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ArrowRight, ChevronRight, Heart, ShoppingBag, Trash2 } from "lucide-vue-next";
import { toast } from "vue-sonner";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import FormCheckbox from "../../components/UI/FormCheckbox.vue";
import QuantityStepper from "../../components/UI/QuantityStepper.vue";
import CartSummary from "./CartSummary.vue";
import CartRecommendations from "./CartRecommendations.vue";
import { cn, formatCurrency } from "../../lib/utils";

const props = defineProps({
    cart: { type: Object, default: null },
    recommendations: { type: Array, default: () => [] },
});
const { t, locale } = useI18n();
const { proxy } = getCurrentInstance();
const money = (value) => formatCurrency(value, locale.value === "id" ? "id-ID" : "en-US");
const localItems = ref((props.cart?.items || []).map((item) => ({ ...item })));
const selectedItemIds = ref(localItems.value.map((item) => item.id));
const savedIds = ref([]);
const busy = ref(false);
const pendingQuantities = ref(new Map());
let quantityTimer;
let disposed = false;
watch(
    () => props.cart?.items,
    (items) => {
        localItems.value = (items || []).map((item) => ({ ...item, quantity: pendingQuantities.value.get(item.id) ?? item.quantity }));
        const ids = new Set(localItems.value.map((item) => item.id));
        selectedItemIds.value = selectedItemIds.value.filter((id) => ids.has(id));
        savedIds.value = savedIds.value.filter((id) => ids.has(id));
    },
    { deep: true },
);
const selectedItems = computed(() => localItems.value.filter((item) => selectedItemIds.value.includes(item.id)));
const subtotal = computed(() => selectedItems.value.reduce((total, item) => total + Number(item.price) * item.quantity, 0));
const allSelected = computed(() => localItems.value.length > 0 && selectedItems.value.length === localItems.value.length);
const updating = computed(() => busy.value || pendingQuantities.value.size > 0);
const canCheckout = computed(() => selectedItems.value.length > 0 && !updating.value);
const toggleAll = () => {
    selectedItemIds.value = allSelected.value ? [] : localItems.value.map((item) => item.id);
};
const toggleItem = (id) => {
    selectedItemIds.value = selectedItemIds.value.includes(id)
        ? selectedItemIds.value.filter((value) => value !== id)
        : [...selectedItemIds.value, id];
};
const toggleSaved = (id) => {
    savedIds.value = savedIds.value.includes(id) ? savedIds.value.filter((value) => value !== id) : [...savedIds.value, id];
};
const checkoutSelected = () => {
    if (canCheckout.value) router.visit(route("frontend.checkout", { cart_item_ids: selectedItemIds.value }));
};
// Serialize writes so an Inertia visit for one row cannot cancel another row's update.
const flushQuantities = () => {
    if (disposed || busy.value || !pendingQuantities.value.size) return;
    const [id, quantity] = pendingQuantities.value.entries().next().value;
    pendingQuantities.value.delete(id);
    busy.value = true;
    router.patch(
        route("frontend.cart.update", id),
        { quantity },
        {
            preserveScroll: true,
            onError: () => {
                const original = props.cart?.items?.find((item) => item.id === id);
                const local = localItems.value.find((item) => item.id === id);
                if (original && local) local.quantity = original.quantity;
                toast.error(t("labels.cart.ui.update_error"));
            },
            onFinish: () => {
                busy.value = false;
                flushQuantities();
            },
        },
    );
};
const changeQuantity = (item, nextQuantity) => {
    if (busy.value || nextQuantity === item.quantity || nextQuantity < 1) return;
    item.quantity = nextQuantity;
    pendingQuantities.value.set(item.id, item.quantity);
    clearTimeout(quantityTimer);
    quantityTimer = setTimeout(flushQuantities, 300);
};
const deleteItems = (ids) => {
    if (disposed || updating.value || !ids.length) return;
    busy.value = true;
    let failed = false;
    router.delete(route("frontend.cart.destroy", ids[0]), {
        preserveScroll: true,
        onError: () => {
            failed = true;
            toast.error(t("labels.cart.ui.delete_error"));
        },
        onCancel: () => {
            failed = true;
        },
        onFinish: () => {
            busy.value = false;
            if (!failed) deleteItems(ids.slice(1));
        },
    });
};
const confirmClear = () => {
    if (updating.value) return;
    proxy.$confirm({
        title: t("labels.cart.ui.clear_title"),
        message: t("labels.cart.ui.clear_description"),
        button: { yes: t("labels.cart.ui.remove_all"), no: t("labels.actions.cancel") },
        callback: (confirmed) => {
            if (confirmed) deleteItems(localItems.value.map((item) => item.id));
        },
    });
};
const discount = (item) => (item.original_price > item.price ? Math.round((1 - item.price / item.original_price) * 100) : 0);
const imageFallback = (event) => {
    event.target.onerror = null;
    event.target.src = "/images/placeholders/product-snapshot.svg";
};
onBeforeUnmount(() => {
    disposed = true;
    clearTimeout(quantityTimer);
});
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('labels.cart.ui.heading')">
        <div
            class="cart-page container mx-auto max-w-7xl px-4 py-6 font-sans md:py-8 lg:py-10"
            :class="localItems.length && 'pb-[calc(7rem+env(safe-area-inset-bottom))] md:pb-10'"
        >
            <nav
                class="cart-breadcrumb text-muted-foreground mb-4 hidden items-center gap-3 text-sm md:flex"
                :aria-label="t('labels.cart.ui.breadcrumb')"
            >
                <Link :href="route('frontend.home')">{{ t("labels.actions.home") }}</Link
                ><ChevronRight class="h-3.5 w-3.5" aria-hidden="true" /><span aria-current="page">{{ t("labels.header.cart") }}</span>
            </nav>
            <div
                v-if="localItems.length"
                class="cart-layout grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-x-6 xl:grid-cols-[minmax(0,1fr)_22.5rem]"
            >
                <section class="cart-items-section min-w-0 lg:col-start-1 lg:row-start-1" :aria-label="t('labels.cart.ui.heading')">
                    <div class="cart-heading mb-3 flex min-h-9 flex-wrap items-center gap-x-3 gap-y-1 md:mb-4">
                        <h1 class="text-foreground text-2xl font-bold tracking-tight md:text-3xl">{{ t("labels.cart.ui.heading") }}</h1>
                        <span class="cart-selected-count text-muted-foreground text-xs sm:text-sm">{{
                            t("labels.cart.ui.selected", { count: selectedItems.length })
                        }}</span
                        ><button
                            class="cart-text-action cart-remove-all text-destructive ml-auto inline-flex min-h-8 items-center gap-1.5 text-xs whitespace-nowrap hover:underline hover:underline-offset-4 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                            :disabled="updating"
                            @click="confirmClear"
                        >
                            <Trash2 class="h-4 w-4" aria-hidden="true" />{{ t("labels.cart.ui.remove_all") }}
                        </button>
                    </div>
                    <label
                        class="cart-select-all bg-secondary/50 text-foreground mb-2 flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold sm:px-4 sm:py-3 sm:text-sm"
                        ><FormCheckbox
                            :model-value="allSelected"
                            :indeterminate="selectedItems.length > 0 && !allSelected"
                            :aria-label="t('labels.cart.select_all')"
                            @update:model-value="toggleAll"
                        /><span>{{ t("labels.cart.ui.select_all_count", { count: localItems.length }) }}</span></label
                    >
                    <div class="cart-item-list grid gap-3" :aria-busy="busy">
                        <article
                            v-for="item in localItems"
                            :key="item.id"
                            class="cart-item border-border grid min-w-0 grid-cols-[1.25rem_4rem_minmax(0,1fr)_4.5rem] gap-x-2 gap-y-2 rounded-xl border bg-white p-3 sm:grid-cols-[1.25rem_5rem_minmax(0,1fr)_5.5rem] sm:gap-x-3 md:grid-cols-[1.25rem_6rem_minmax(0,1fr)_7rem] xl:grid-cols-[1.25rem_6rem_minmax(10rem,1fr)_7rem_6rem_7.5rem] xl:gap-x-4 xl:p-4"
                            :data-item-id="item.id"
                        >
                            <FormCheckbox
                                class="row-span-3 self-center"
                                :model-value="selectedItemIds.includes(item.id)"
                                :aria-label="t('labels.cart.select_item')"
                                @update:model-value="toggleItem(item.id)"
                            />
                            <Link
                                class="cart-item-image bg-secondary/60 row-span-3 aspect-square self-start overflow-hidden rounded-lg"
                                :href="route('frontend.product-detail', item.product?.slug)"
                                ><img
                                    class="h-full w-full object-cover"
                                    :src="item.product?.thumbnail || '/images/placeholders/product-snapshot.svg'"
                                    :alt="item.product?.name"
                                    @error="imageFallback"
                            /></Link>
                            <div class="cart-item-info col-start-3 row-start-1 min-w-0 xl:row-span-2">
                                <Link
                                    class="cart-item-name text-foreground hover:text-primary line-clamp-2 text-xs leading-5 font-semibold break-words transition-colors sm:text-sm"
                                    :href="route('frontend.product-detail', item.product?.slug)"
                                    >{{ item.product?.name }}</Link
                                >
                                <span
                                    v-if="item.product_variant?.variant_name"
                                    class="cart-variant text-muted-foreground mt-1 block text-[10px] leading-4 break-words sm:text-xs"
                                    >{{ t("labels.cart.ui.variant") }}: {{ item.product_variant.variant_name }}</span
                                ><span
                                    :class="
                                        cn(
                                            'cart-stock mt-1 inline-flex items-center gap-1.5 text-[10px] text-green-700 sm:text-xs',
                                            item.product?.stock === 0 && 'cart-stock--unavailable text-destructive',
                                        )
                                    "
                                    ><i class="h-2 w-2 shrink-0 rounded-full bg-current" />{{
                                        t(item.product?.stock === 0 ? "labels.cart.ui.stock_unavailable" : "labels.cart.ui.stock_available")
                                    }}</span
                                >
                            </div>
                            <div class="cart-item-price col-start-3 row-start-2 min-w-0 xl:col-start-4 xl:row-start-1">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                    <strong class="text-primary text-xs font-semibold whitespace-nowrap sm:text-sm xl:text-base">{{
                                        money(item.price)
                                    }}</strong
                                    ><del v-if="discount(item)" class="text-muted-foreground text-[10px] whitespace-nowrap sm:text-xs">{{
                                        money(item.original_price)
                                    }}</del>
                                </div>
                                <span
                                    v-if="discount(item)"
                                    class="cart-discount bg-destructive text-destructive-foreground mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold sm:px-2.5 sm:text-xs"
                                    >{{ t("labels.cart.ui.discount", { percent: discount(item) }) }}</span
                                >
                            </div>
                            <QuantityStepper
                                :model-value="item.quantity"
                                :max="item.product?.stock"
                                :disabled="busy"
                                :decrease-label="t('labels.cart.ui.decrease', { name: item.product?.name })"
                                :increase-label="t('labels.cart.ui.increase', { name: item.product?.name })"
                                :quantity-label="t('labels.cart.ui.quantity', { name: item.product?.name })"
                                class="cart-quantity col-start-4 row-start-1 xl:col-start-5"
                                @update:model-value="changeQuantity(item, $event)"
                            />
                            <div class="cart-item-subtotal hidden min-w-0 xl:col-start-6 xl:row-start-1 xl:block">
                                <span class="text-muted-foreground block text-xs">{{ t("labels.cart.subtotal") }}</span
                                ><strong class="text-primary mt-1 block text-base font-semibold whitespace-nowrap">{{
                                    money(item.price * item.quantity)
                                }}</strong>
                            </div>
                            <div
                                class="cart-item-actions col-start-4 row-start-2 row-end-4 flex flex-col items-end justify-end gap-1 xl:col-start-4 xl:col-end-7 xl:row-start-2 xl:flex-row xl:gap-5"
                            >
                                <button
                                    class="cart-text-action cart-save text-foreground inline-flex min-h-7 items-center gap-1 text-[10px] whitespace-nowrap hover:underline hover:underline-offset-4 sm:text-xs"
                                    :class="savedIds.includes(item.id) && 'text-primary'"
                                    :aria-pressed="savedIds.includes(item.id)"
                                    @click="toggleSaved(item.id)"
                                >
                                    <Heart class="h-4 w-4" :fill="savedIds.includes(item.id) ? 'currentColor' : 'none'" aria-hidden="true" /><span>{{
                                        t(savedIds.includes(item.id) ? "labels.cart.ui.saved" : "labels.cart.ui.save")
                                    }}</span
                                    ><span v-if="!savedIds.includes(item.id)" class="cart-save-suffix hidden xl:inline">{{
                                        t("labels.cart.ui.for_later")
                                    }}</span></button
                                ><button
                                    class="cart-text-action cart-delete text-destructive inline-flex min-h-7 items-center gap-1 text-[10px] whitespace-nowrap hover:underline hover:underline-offset-4 disabled:cursor-not-allowed disabled:opacity-50 sm:text-xs"
                                    :disabled="updating"
                                    :aria-label="t('labels.cart.ui.remove_item', { name: item.product?.name })"
                                    @click="deleteItems([item.id])"
                                >
                                    <Trash2 class="h-4 w-4" aria-hidden="true" />{{ t("labels.actions.delete") }}
                                </button>
                            </div>
                        </article>
                    </div>
                </section>
                <CartSummary
                    :subtotal="subtotal"
                    :count="selectedItems.length"
                    :can-checkout="canCheckout"
                    :updating="updating"
                    @checkout="checkoutSelected"
                />
                <CartRecommendations :recommendations="props.recommendations" />
            </div>
            <section v-else class="cart-empty from-secondary/40 to-secondary rounded-3xl bg-gradient-to-br py-16 text-center md:py-20">
                <ShoppingBag class="text-primary mx-auto mb-6 h-16 w-16" aria-hidden="true" />
                <h1 class="text-foreground text-2xl font-bold md:text-3xl">{{ t("labels.cart.empty_title") }}</h1>
                <p class="text-muted-foreground mx-auto my-3 max-w-lg px-4 text-sm">{{ t("labels.cart.empty_description") }}</p>
                <Link
                    class="cart-primary-button bg-primary text-primary-foreground hover:bg-primary/90 mx-auto mt-6 inline-flex min-h-11 w-fit items-center justify-center gap-2 rounded-xl px-7 py-3 text-sm font-semibold transition-colors"
                    :href="route('frontend.products')"
                    >{{ t("labels.actions.start_shopping") }}<ArrowRight class="h-4 w-4" aria-hidden="true"
                /></Link>
            </section>
        </div>
    </TemplateWrapper>
</template>
