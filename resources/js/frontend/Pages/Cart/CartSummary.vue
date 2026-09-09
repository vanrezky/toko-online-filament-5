<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ArrowLeft, ArrowRight } from "lucide-vue-next";
import { formatCurrency } from "../../lib/utils";
import Button from "../../components/UI/Button.vue";

defineProps({ subtotal: Number, count: Number, canCheckout: Boolean, updating: Boolean });
defineEmits(["checkout"]);
const { t, locale } = useI18n();
const money = (value) => formatCurrency(value, locale.value === "id" ? "id-ID" : "en-US");
</script>

<template>
    <aside
        class="cart-summary md:border-border flex min-w-0 flex-col gap-3 md:rounded-xl md:border md:bg-white md:p-5 md:shadow-sm lg:sticky lg:top-20 lg:col-start-2 lg:row-span-3 lg:row-start-1"
        :aria-label="t('labels.cart.summary_title')"
    >
        <h2 class="cart-summary-heading text-foreground hidden text-xl font-bold tracking-tight md:block">{{ t("labels.cart.summary_title") }}</h2>
        <div
            class="cart-totals border-border order-2 rounded-xl border bg-white p-4 md:order-none md:rounded-none md:border-x-0 md:border-b-0 md:px-0 md:pt-4 md:pb-0"
        >
            <h2 class="text-foreground mb-4 text-lg font-bold md:hidden">{{ t("labels.cart.summary_title") }}</h2>
            <dl>
                <div class="mb-2 flex items-baseline justify-between gap-3 text-sm">
                    <dt class="text-muted-foreground">{{ t("labels.cart.ui.subtotal_count", { count }) }}</dt>
                    <dd class="text-foreground text-right font-medium" data-test="cart-subtotal">{{ money(subtotal) }}</dd>
                </div>
                <div class="mb-2 flex items-baseline justify-between gap-3 text-sm">
                    <dt class="text-muted-foreground">{{ t("labels.cart.shipping_cost") }}</dt>
                    <dd class="cart-shipping-value text-foreground text-right text-xs font-medium">{{ t("labels.cart.ui.at_checkout") }}</dd>
                </div>
                <div class="cart-grand-total border-border mt-3 flex items-center justify-between gap-3 border-t pt-3 md:mt-5 md:pt-4">
                    <dt class="text-foreground text-sm font-semibold md:text-base">{{ t("labels.cart.ui.total") }}</dt>
                    <dd class="text-primary text-xl font-bold whitespace-nowrap md:text-2xl">{{ money(subtotal) }}</dd>
                </div>
            </dl>
        </div>
        <div
            class="cart-checkout-bar border-border fixed inset-x-0 bottom-0 z-40 grid grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] items-center gap-3 rounded-t-2xl border-t bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(0,0,0,0.08)] backdrop-blur md:static md:block md:border-0 md:bg-transparent md:p-0 md:shadow-none md:backdrop-blur-none"
        >
            <div class="cart-mobile-total flex min-w-0 flex-col md:hidden">
                <span class="text-foreground text-xs">{{ t("labels.cart.ui.total") }}</span
                ><strong class="text-primary truncate text-lg font-bold sm:text-xl">{{ money(subtotal) }}</strong
                ><small class="text-muted-foreground text-[9px]">{{ t("labels.cart.ui.shipping_at_checkout") }}</small>
            </div>
            <Button
                variant="primary"
                class="cart-primary-button min-h-12 w-full gap-2 px-3 text-xs md:min-h-11 md:text-sm"
                data-test="cart-checkout"
                :disabled="!canCheckout"
                @click="$emit('checkout')"
            >
                <span>{{ t(updating ? "labels.cart.ui.updating" : "labels.cart.ui.checkout") }}</span
                ><ArrowRight class="h-4 w-4" aria-hidden="true" />
            </Button>
        </div>
        <Link
            class="cart-outline-button cart-continue border-primary text-primary hover:bg-primary/5 hidden min-h-11 w-full items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition-colors md:inline-flex"
            :href="route('frontend.products')"
            ><ArrowLeft class="h-4 w-4" aria-hidden="true" />{{ t("labels.actions.continue_shopping") }}</Link
        >
    </aside>
</template>
