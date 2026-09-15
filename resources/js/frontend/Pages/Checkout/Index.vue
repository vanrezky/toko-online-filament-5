<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, computed, watch, onMounted } from "vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import axios from "axios";
import {
    AlertCircle,
    CalendarDays,
    Check,
    ChevronRight,
    CreditCard,
    Edit3,
    Loader2,
    Plus,
    ShieldCheck,
    Store,
    Tag,
    Ticket,
    Truck,
    WalletCards,
    X,
} from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { installmentService } from "../../services/installmentService";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import FormRadio from "../../components/UI/FormRadio.vue";
import FormTextarea from "../../components/UI/FormTextarea.vue";
import Card from "../../components/UI/Card.vue";
import AddressForm from "../../components/AddressForm.vue";
import { formatCurrency } from "../../lib/utils";
import { createLatestRequestGate, getCourierLogo, reconcileShippingMethods } from "../../lib/shippingMethods";

const props = defineProps({
    cart: Object,
    addresses: Array,
    provinces: Array,
    shippingMethods: Array,
    pendingVouchers: Object,
    validatedVouchers: Object,
    activeGateway: String,
    midtransAvailable: Boolean,
    installmentPlans: Array,
    creditLimit: Object,
    installmentMinOrderAmount: Number,
    balance: Object,
    cartItemIds: Array,
});

const { t } = useI18n();

const form = useForm({
    address_id: props.addresses?.find((a) => a.is_featured)?.id || props.addresses?.[0]?.id || null,
    shipping_methods: {},
    payment_method: null,
    notes: "",
    cart_item_ids: props.cartItemIds || null,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
});

const shippingResults = ref([]);
const isLoadingShipping = ref(false);
const isValidatingVouchers = ref(false);
const localValidatedVouchers = ref(props.validatedVouchers || { shipping: null, product: null });
const voucherCode = ref("");
const isApplyingVoucher = ref(false);
const voucherError = ref(null);
const isProcessingOrder = ref(false);
const shippingRequestGate = createLatestRequestGate();
const isAddressModalOpen = ref(false);
const editingAddress = ref(null);
const showAllAddresses = ref(false);
const displayedAddresses = computed(() => {
    const addresses = props.addresses || [];
    const selected = addresses.find((address) => address.id === form.address_id);
    const ordered = selected ? [selected, ...addresses.filter((address) => address.id !== selected.id)] : addresses;
    return showAllAddresses.value ? ordered : ordered.slice(0, 2);
});

const selectedPaymentType = ref("full");
const selectedInstallmentPlan = ref(null);
const installmentCalculations = ref(null);
const isLoadingInstallment = ref(false);
const creditLimitRemaining = ref(props.creditLimit?.remaining || 0);
const balanceAvailable = computed(() => Number(props.balance?.available || 0));
const isBalanceEnabled = computed(() => props.balance?.enabled === true);
const isBalanceSufficient = computed(() => balanceAvailable.value >= grandTotal.value);
const balanceShortfall = computed(() => Math.max(0, grandTotal.value - balanceAvailable.value));
const isCreditLimitEnforced = computed(() => props.creditLimit?.enforced !== false);
const installmentMinOrderAmount = computed(() => Number(props.installmentMinOrderAmount || 1000000));

watch(
    () => props.validatedVouchers,
    (newVal) => {
        localValidatedVouchers.value = newVal || { shipping: null, product: null };
    },
    { deep: true },
);

const fetchShippingCosts = async () => {
    if (!form.address_id) return;

    const requestId = shippingRequestGate.start();
    isLoadingShipping.value = true;
    try {
        const response = await axios.get(route("frontend.checkout.shipping-costs"), {
            params: {
                address_id: form.address_id,
                cart_item_ids: form.cart_item_ids,
            },
        });

        if (!shippingRequestGate.isLatest(requestId)) {
            return;
        }

        shippingResults.value = response.data;

        const { methods, hasFallback } = reconcileShippingMethods(form.shipping_methods, response.data);
        form.shipping_methods = methods;

        if (hasFallback) {
            toast.warning(t("labels.checkout.shipping_method_changed"));
        }
    } catch (error) {
        if (!shippingRequestGate.isLatest(requestId)) {
            return;
        }

        console.error("Failed to fetch shipping costs", error);
    } finally {
        if (shippingRequestGate.isLatest(requestId)) {
            isLoadingShipping.value = false;
        }
    }
};

const validateVouchers = async () => {
    isValidatingVouchers.value = true;
    try {
        const response = await axios.get("/api/vouchers/validate-cookie", {
            withCredentials: true,
        });
        if (response.data.success) {
            localValidatedVouchers.value = response.data.data;
        }
    } catch (error) {
        console.error("Failed to validate vouchers:", error);
    } finally {
        isValidatingVouchers.value = false;
    }
};

watch(
    () => form.address_id,
    () => {
        fetchShippingCosts();
    },
);

onMounted(() => {
    if (form.address_id) {
        fetchShippingCosts();
    }
    validateVouchers();
});

const openAddressModal = (address = null) => {
    editingAddress.value = address;
    isAddressModalOpen.value = true;
};

const closeAddressModal = () => {
    isAddressModalOpen.value = false;
    editingAddress.value = null;
};

const addressSaved = (draft) => {
    const editedAddressId = editingAddress.value?.id || null;
    closeAddressModal();
    router.reload({
        only: ["addresses"],
        onSuccess: (page) => {
            const address = (page.props.addresses || []).find((item) =>
                editedAddressId
                    ? String(item.id) === String(editedAddressId)
                    : item.name === draft.name && item.phone === draft.phone && item.address === draft.address,
            );
            if (!address) return;

            if (!editedAddressId) {
                form.address_id = address.id;
            } else if (String(form.address_id) === String(address.id)) {
                fetchShippingCosts();
            }
        },
    });
};

const items = computed(() => props.cart?.items || []);
const imageFallback = (event) => {
    event.target.onerror = null;
    event.target.src = "/images/placeholders/product-snapshot.svg";
};
const roundIdr = (amount) => Math.round(Number(amount || 0));
const subtotal = computed(() => {
    return items.value.reduce((total, item) => total + roundIdr(item.original_price || item.price) * item.quantity, 0);
});

const saleProductDiscount = computed(() => {
    return items.value.reduce((total, item) => {
        const original = roundIdr(item.original_price || item.price);
        const final = roundIdr(item.price);

        return total + Math.max(0, original - final) * item.quantity;
    }, 0);
});

const shippingDiscount = computed(() => {
    return Math.min(shippingFee.value, roundIdr(localValidatedVouchers.value?.shipping?.discount_amount));
});

const voucherProductDiscount = computed(() => {
    const productTotal = subtotal.value - saleProductDiscount.value;

    return Math.min(productTotal, roundIdr(localValidatedVouchers.value?.product?.discount_amount));
});

const totalVoucherDiscount = computed(() => {
    return shippingDiscount.value + voucherProductDiscount.value;
});

const hasAnyVoucher = computed(() => {
    return localValidatedVouchers.value?.shipping || localValidatedVouchers.value?.product;
});

const hasInvalidVoucher = computed(() => {
    const shipping = localValidatedVouchers.value?.shipping;
    const product = localValidatedVouchers.value?.product;
    return (shipping && !shipping.valid) || (product && !product.valid);
});

const shippingFee = computed(() => {
    return Object.values(form.shipping_methods).reduce((sum, method) => sum + roundIdr(method.price), 0);
});

const hasShippingMethodsSelected = computed(() => {
    return Object.keys(form.shipping_methods || {}).length > 0;
});

const isPickupOnlySelection = computed(() => {
    const methods = Object.values(form.shipping_methods || {});
    if (!methods.length) return false;

    return methods.every((method) => String(method.courier_code || "").toUpperCase() === "PICKUP");
});

const requiresAddressSelection = computed(() => {
    if (!hasShippingMethodsSelected.value) {
        return true;
    }

    return !isPickupOnlySelection.value;
});

const discountedShippingFee = computed(() => {
    return Math.max(0, shippingFee.value - shippingDiscount.value);
});

const total = computed(() => subtotal.value + discountedShippingFee.value);
const grandTotal = computed(() => {
    return subtotal.value - saleProductDiscount.value + discountedShippingFee.value - voucherProductDiscount.value;
});

const isInstallmentEligible = computed(() => {
    return grandTotal.value >= installmentMinOrderAmount.value;
});

const selectPaymentType = (type) => {
    selectedPaymentType.value = type;
    form.payment_method = null;
    if (type === "full") {
        selectedInstallmentPlan.value = null;
    }
};

const selectMidtrans = () => {
    selectedPaymentType.value = "full";
    selectedInstallmentPlan.value = null;
    form.payment_method = "midtrans";
};

const selectedPaymentOption = computed({
    get: () => (form.payment_method === "midtrans" ? "midtrans" : selectedPaymentType.value),
    set: (option) => {
        if (option === "midtrans") {
            selectMidtrans();
            return;
        }

        selectPaymentType(option);
    },
});

const calculateInstallments = async () => {
    isLoadingInstallment.value = true;
    try {
        const data = await installmentService.calculate(grandTotal.value);
        installmentCalculations.value = data.data;
    } catch (error) {
        console.error("Failed to calculate installments", error);
    } finally {
        isLoadingInstallment.value = false;
    }
};

watch(selectedPaymentType, (type) => {
    if (type === "installment") {
        if (!isInstallmentEligible.value) {
            selectedPaymentType.value = "full";
            return;
        }

        calculateInstallments();
    } else {
        installmentCalculations.value = null;
        selectedInstallmentPlan.value = null;
    }
});

watch(isInstallmentEligible, (eligible) => {
    if (!eligible && selectedPaymentType.value === "installment") {
        selectedPaymentType.value = "full";
        selectedInstallmentPlan.value = null;
        installmentCalculations.value = null;
    }
});

const isOverLimit = computed(() => {
    if (!isCreditLimitEnforced.value) return false;
    if (!creditLimitRemaining.value) return false;

    // For installment, check if selected plan's total exceeds limit
    if (selectedPaymentType.value === "installment" && selectedInstallmentPlan.value && installmentCalculations.value) {
        const plan = installmentCalculations.value.plans.find((p) => p.id === selectedInstallmentPlan.value.id);
        return plan ? plan.total_amount > creditLimitRemaining.value : false;
    }

    // For full payment, check if grandTotal exceeds limit
    if (selectedPaymentType.value === "full" && form.payment_method !== "midtrans") {
        return grandTotal.value > creditLimitRemaining.value;
    }
    if (selectedPaymentType.value === "balance") {
        return false;
    }

    return false;
});

const canSubmitOrder = computed(() => {
    if (selectedPaymentType.value === "installment" && !selectedInstallmentPlan.value) {
        return false;
    }
    if (selectedPaymentType.value === "installment" && !isInstallmentEligible.value) {
        return false;
    }
    if (isOverLimit.value) {
        return false;
    }
    if (selectedPaymentType.value === "balance" && !isBalanceSufficient.value) {
        return false;
    }
    return (
        !isValidatingVouchers.value &&
        !hasInvalidVoucher.value &&
        hasShippingMethodsSelected.value &&
        (!requiresAddressSelection.value || !!form.address_id)
    );
});

const paymentError = computed(() => {
    const paymentTypeError = form.errors.payment_type;
    const paymentMethodError = form.errors.payment_method;

    if (Array.isArray(paymentTypeError)) {
        return paymentTypeError[0] || null;
    }

    if (paymentTypeError) {
        return paymentTypeError;
    }

    if (Array.isArray(paymentMethodError)) {
        return paymentMethodError[0] || null;
    }

    return paymentMethodError || null;
});

const submitOrder = async () => {
    if (isValidatingVouchers.value || isProcessingOrder.value) {
        return;
    }

    isProcessingOrder.value = true;

    try {
        const submitData = {
            ...form.data(),
            payment_type: selectedPaymentType.value,
            installment_plan_id: selectedInstallmentPlan.value?.id || null,
        };

        const response = await axios.post(route("frontend.checkout.store"), submitData);

        if (response.data.success) {
            const payment = response.data.payment;

            if (payment && payment.provider === "midtrans" && payment.snap_token) {
                const isProduction = payment.mode === "production";
                const scriptUrl = isProduction ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";

                const loadSnapScript = new Promise((resolve) => {
                    if (document.getElementById("midtrans-script")) {
                        return resolve();
                    }
                    const script = document.createElement("script");
                    script.id = "midtrans-script";
                    script.src = scriptUrl;
                    script.setAttribute("data-client-key", payment.client_key);
                    script.onload = resolve;
                    document.head.appendChild(script);
                });

                await loadSnapScript;

                window.snap.pay(payment.snap_token, {
                    onSuccess: function () {
                        window.location.href = route("frontend.orders.show", response.data.transaction_uuid);
                    },
                    onPending: function () {
                        window.location.href = route("frontend.orders.show", response.data.transaction_uuid);
                    },
                    onError: function () {
                        window.location.href = route("frontend.orders.show", response.data.transaction_uuid);
                    },
                    onClose: function () {
                        window.location.href = route("frontend.orders.show", response.data.transaction_uuid);
                    },
                });
            } else if (payment && payment.payment_url) {
                window.location.href = payment.payment_url;
            } else {
                window.location.href = route("frontend.orders.show", response.data.transaction_uuid);
            }
        }
    } catch (error) {
        console.error("Checkout failed", error);
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors || {};

            form.clearErrors();
            Object.entries(errors).forEach(([field, messages]) => {
                if (Array.isArray(messages)) {
                    form.setError(field, messages[0] || "");
                    return;
                }

                form.setError(field, messages || "");
            });

            if (errors.payment_type && !errors.payment_method) {
                const paymentTypeMessage = Array.isArray(errors.payment_type) ? errors.payment_type[0] || "" : errors.payment_type;

                form.setError("payment_method", paymentTypeMessage);
            }
        } else {
            alert(error.response?.data?.error || t("messages.error.checkout_failed"));
        }
    } finally {
        isProcessingOrder.value = false;
    }
};

const removeVoucher = async (type) => {
    try {
        await axios.post("/api/vouchers/remove", { type }, { withCredentials: true });
        localValidatedVouchers.value[type] = null;
    } catch (error) {
        console.error("Failed to remove voucher:", error);
    }
};

const applyVoucher = async () => {
    if (!voucherCode.value.trim()) return;

    isApplyingVoucher.value = true;
    voucherError.value = null;

    try {
        const response = await axios.post("/api/vouchers/apply", { code: voucherCode.value.trim() }, { withCredentials: true });

        if (response.data.success) {
            voucherCode.value = "";
            if (response.data.data?.pending) {
                const pending = response.data.data.pending;
                localValidatedVouchers.value = response.data.data.vouchers || localValidatedVouchers.value;
            }
            await validateVouchers();
        } else {
            voucherError.value = response.data.error?.message || t("messages.error.voucher_invalid");
        }
    } catch (error) {
        voucherError.value = error.response?.data?.error?.message || t("messages.error.generic");
    } finally {
        isApplyingVoucher.value = false;
    }
};
</script>

<template>
    <TemplateWrapper :shell="false" :title="t('labels.checkout.heading')">
        <PageShell container :title="t('labels.checkout.heading')" :description="t('labels.checkout.description')">
            <div class="grid grid-cols-1 gap-6 pb-24 lg:grid-cols-12 lg:gap-8 lg:pb-0">
                <!-- Left Side: Forms -->
                <div class="order-1 flex min-w-0 flex-col gap-4 lg:order-1 lg:col-span-8">
                    <!-- Shipping Address Section -->
                    <Card as="section" class="order-1 rounded-2xl p-4 sm:p-5 md:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span
                                    class="bg-primary text-primary-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                                    >1</span
                                >
                                <div>
                                    <h2 class="text-foreground text-base font-bold sm:text-lg">{{ t("labels.checkout.shipping_address") }}</h2>
                                    <p class="text-muted-foreground mt-0.5 text-xs">{{ t("labels.address.description") }}</p>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :icon="Plus"
                                :aria-label="t('labels.address.add')"
                                class="shrink-0"
                                @click="openAddressModal()"
                            >
                                <span class="hidden whitespace-nowrap sm:inline">{{ t("labels.address.add") }}</span>
                            </Button>
                        </div>

                        <div
                            v-if="isPickupOnlySelection"
                            class="border-primary/20 bg-primary/5 text-foreground mt-5 flex items-start gap-3 rounded-xl border p-4 text-sm leading-relaxed"
                        >
                            <Store class="text-primary mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                            {{ t("labels.checkout.pickup_no_address_required") }}
                        </div>

                        <div
                            v-else
                            class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2"
                            role="radiogroup"
                            :aria-label="t('labels.checkout.shipping_address')"
                        >
                            <div v-for="address in displayedAddresses" :key="address.id" class="relative min-w-0">
                                <div
                                    @click="form.address_id = address.id"
                                    @keydown.enter="form.address_id = address.id"
                                    @keydown.space.prevent="form.address_id = address.id"
                                    role="radio"
                                    tabindex="0"
                                    :aria-checked="form.address_id === address.id"
                                    class="focus-visible:ring-primary/30 min-w-0 cursor-pointer rounded-xl border p-4 pr-16 text-left transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    :class="
                                        form.address_id === address.id
                                            ? 'border-primary bg-primary/5 ring-primary/20 ring-1'
                                            : 'border-border bg-background hover:border-primary/50 hover:bg-secondary/40'
                                    "
                                >
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                            :class="
                                                form.address_id === address.id
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-muted-foreground/50'
                                            "
                                        >
                                            <Check v-if="form.address_id === address.id" class="h-3 w-3" stroke-width="3" aria-hidden="true" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-foreground text-sm font-bold">{{ address.name }}</h3>
                                            <p class="text-muted-foreground mt-0.5 text-xs">{{ address.phone }}</p>
                                        </div>
                                        <span
                                            v-if="address.is_featured"
                                            class="bg-primary text-primary-foreground shrink-0 rounded-full px-2 py-1 text-[10px] font-bold"
                                        >
                                            {{ t("labels.address.default_badge") }}
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground mt-3 line-clamp-3 text-xs leading-relaxed">
                                        {{ address.address }}<br />
                                        {{ address.village_name }}, {{ address.sub_district_name }}, {{ address.district_name }},
                                        {{ address.province_name }}, {{ address.postal_code }}
                                    </p>
                                </div>
                                <Button
                                    v-if="address.can_customer_manage"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    :aria-label="t('labels.address.edit')"
                                    class="absolute top-1/2 right-3 -translate-y-1/2"
                                    @click.stop="openAddressModal(address)"
                                >
                                    <Edit3 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        <Button
                            v-if="addresses?.length > 1 && !isPickupOnlySelection"
                            type="button"
                            variant="outline"
                            block
                            class="bg-background mt-3 border-dashed text-xs"
                            @click="showAllAddresses = !showAllAddresses"
                        >
                            {{
                                showAllAddresses
                                    ? t("labels.actions.show_less")
                                    : t("labels.checkout.show_other_addresses", { count: addresses.length - displayedAddresses.length })
                            }}
                            <ChevronRight class="h-4 w-4" :class="showAllAddresses ? '-rotate-90' : 'rotate-90'" aria-hidden="true" />
                        </Button>
                        <p v-if="form.errors.address_id && !isPickupOnlySelection" class="text-destructive mt-3 text-xs" role="alert">
                            {{ form.errors.address_id }}
                        </p>
                    </Card>

                    <!-- Shipping Method Section -->
                    <Card as="section" class="order-2 rounded-2xl p-4 sm:p-5 md:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span
                                    class="bg-primary text-primary-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                                    >2</span
                                >
                                <div>
                                    <h2 class="text-foreground text-base font-bold sm:text-lg">{{ t("labels.checkout.shipping_method") }}</h2>
                                    <p class="text-muted-foreground mt-0.5 text-xs">{{ t("labels.home.trust.shipping_description") }}</p>
                                </div>
                            </div>
                            <div
                                v-if="isLoadingShipping"
                                class="text-muted-foreground flex shrink-0 items-center gap-2"
                                role="status"
                                aria-live="polite"
                            >
                                <Loader2 class="text-primary h-4 w-4 animate-spin" aria-hidden="true" />
                                <span class="hidden text-xs font-semibold sm:inline">{{ t("labels.actions.updating") }}</span>
                            </div>
                        </div>

                        <div
                            v-if="isLoadingShipping"
                            class="mt-5 grid animate-pulse gap-3 sm:grid-cols-2"
                            role="status"
                            :aria-label="t('labels.actions.loading')"
                        >
                            <div v-for="i in 6" :key="i" class="bg-secondary h-20 rounded-xl" />
                        </div>
                        <div v-else-if="shippingResults.length > 0" class="mt-5 space-y-5">
                            <div v-for="warehouse in shippingResults" :key="warehouse.warehouse_id" class="space-y-3">
                                <h3
                                    v-if="shippingResults.length > 1"
                                    class="border-border text-muted-foreground flex items-center gap-2 border-b pb-2 text-xs font-semibold"
                                >
                                    <Truck class="text-primary h-4 w-4" aria-hidden="true" />
                                    {{ t("labels.checkout.shipped_from", { warehouse: warehouse.warehouse_name }) }}
                                </h3>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label
                                        v-for="option in warehouse.options"
                                        :key="option.courier_code"
                                        class="focus-within:ring-primary/25 flex min-h-16 cursor-pointer items-start gap-3 rounded-xl border px-3 py-2 transition-colors focus-within:ring-2"
                                        :class="
                                            form.shipping_methods[warehouse.warehouse_id]?.courier_code === option.courier_code
                                                ? 'border-primary bg-primary/5'
                                                : 'border-border bg-background hover:border-primary/50 hover:bg-secondary/40'
                                        "
                                    >
                                        <FormRadio
                                            type="radio"
                                            :name="'shipping_' + warehouse.warehouse_id"
                                            @change="
                                                form.shipping_methods[warehouse.warehouse_id] = {
                                                    ...option,
                                                    weight: warehouse.weight,
                                                }
                                            "
                                            :checked="form.shipping_methods[warehouse.warehouse_id]?.courier_code === option.courier_code"
                                            class="h-5 w-5"
                                        />
                                        <span class="bg-secondary text-primary mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                            <img
                                                v-if="getCourierLogo(option.courier_code)"
                                                :src="getCourierLogo(option.courier_code)"
                                                :alt="option.courier_name"
                                                class="h-7 w-7 object-contain"
                                            />
                                            <Store
                                                v-else-if="String(option.courier_code).toUpperCase() === 'PICKUP'"
                                                class="h-5 w-5"
                                                aria-hidden="true"
                                            />
                                            <Truck v-else class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="text-foreground block text-sm leading-5 font-bold whitespace-normal">{{
                                                option.courier_name
                                            }}</span>
                                            <span class="text-muted-foreground mt-0.5 block text-sm leading-5 whitespace-normal">{{
                                                t("labels.checkout.estimation", { estimation: option.estimation })
                                            }}</span>
                                        </span>
                                        <span
                                            class="shrink-0 text-xs font-bold"
                                            :class="Number(option.price) === 0 ? 'text-emerald-600' : 'text-primary'"
                                        >
                                            {{ Number(option.price) === 0 ? t("labels.checkout.shipping_free") : formatCurrency(option.price) }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else-if="!isLoadingShipping && form.address_id"
                            class="border-border mt-5 rounded-xl border border-dashed p-8 text-center"
                        >
                            <p class="text-muted-foreground text-xs">{{ t("messages.error.no_shipping_options") }}</p>
                        </div>
                        <div v-else-if="!form.address_id" class="border-border mt-5 rounded-xl border border-dashed p-8 text-center">
                            <p class="text-muted-foreground text-xs">{{ t("messages.error.select_address_first") }}</p>
                        </div>
                        <p v-if="form.errors.shipping_methods" class="text-destructive mt-3 text-xs" role="alert">
                            {{ form.errors.shipping_methods }}
                        </p>
                    </Card>

                    <!-- Payment Method Section -->
                    <Card v-if="activeGateway !== 'midtrans' || isBalanceEnabled" as="section" class="order-3 rounded-2xl p-4 sm:p-5 md:p-6">
                        <div class="flex items-start gap-3">
                            <span
                                class="bg-primary text-primary-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                                >3</span
                            >
                            <div>
                                <h2 class="text-foreground text-base font-bold sm:text-lg">{{ t("labels.payment.question") }}</h2>
                                <p class="text-muted-foreground mt-0.5 text-xs">{{ t("labels.payment.helper") }}</p>
                            </div>
                        </div>

                        <div role="radiogroup" :aria-label="t('labels.payment.question')">
                            <div
                                v-if="isCreditLimitEnforced"
                                class="border-border bg-secondary/60 mt-5 flex items-center justify-between gap-4 rounded-xl border px-4 py-3"
                            >
                                <span class="text-muted-foreground text-xs font-medium">{{ t("labels.checkout.credit_limit_remaining") }}</span>
                                <span class="text-sm font-bold" :class="isOverLimit ? 'text-destructive' : 'text-emerald-600'">
                                    {{ formatCurrency(creditLimitRemaining) }}
                                </span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <label
                                    v-if="midtransAvailable"
                                    class="border-border bg-background hover:border-primary/50 hover:bg-secondary/40 flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors"
                                    :class="selectedPaymentOption === 'midtrans' ? 'border-primary bg-primary/5' : ''"
                                >
                                    <FormRadio
                                        type="radio"
                                        name="checkout_payment_method"
                                        value="midtrans"
                                        v-model="selectedPaymentOption"
                                        class="h-5 w-5"
                                    />
                                    <span class="bg-secondary text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                        ><WalletCards class="h-4 w-4" aria-hidden="true"
                                    /></span>
                                    <span class="min-w-0"
                                        ><span class="text-foreground block text-sm font-semibold">Midtrans</span
                                        ><span class="text-muted-foreground block text-xs">{{ t("labels.payment.midtrans_description") }}</span></span
                                    >
                                </label>
                                <label
                                    v-if="isBalanceEnabled"
                                    class="flex min-w-0 items-start gap-3 rounded-xl border p-3 transition-colors"
                                    :class="[
                                        selectedPaymentOption === 'balance' ? 'border-primary bg-primary/5' : 'border-border bg-background',
                                        !isBalanceSufficient
                                            ? 'cursor-not-allowed opacity-60'
                                            : 'hover:border-primary/50 hover:bg-secondary/40 cursor-pointer',
                                    ]"
                                >
                                    <FormRadio
                                        type="radio"
                                        name="checkout_payment_method"
                                        value="balance"
                                        v-model="selectedPaymentOption"
                                        :disabled="!isBalanceSufficient"
                                        class="mt-0.5 h-5 w-5"
                                    />
                                    <span class="bg-secondary text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                        ><WalletCards class="h-4 w-4" aria-hidden="true"
                                    /></span>
                                    <div class="min-w-0">
                                        <span class="text-foreground block text-sm leading-5 font-semibold">{{ t("labels.payment.balance") }}</span>
                                        <span class="text-muted-foreground mt-1 block text-xs leading-4">
                                            {{
                                                isBalanceSufficient
                                                    ? t("labels.payment.balance_available", { amount: formatCurrency(balanceAvailable) })
                                                    : t("labels.payment.balance_short", { amount: formatCurrency(balanceShortfall) })
                                            }}
                                        </span>
                                    </div>
                                </label>
                                <label
                                    class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors"
                                    :class="
                                        selectedPaymentOption === 'full'
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border bg-background hover:border-primary/50 hover:bg-secondary/40'
                                    "
                                >
                                    <FormRadio
                                        type="radio"
                                        name="checkout_payment_method"
                                        value="full"
                                        v-model="selectedPaymentOption"
                                        class="mt-0.5 h-5 w-5"
                                    />
                                    <span class="bg-secondary text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                        ><CreditCard class="h-4 w-4" aria-hidden="true"
                                    /></span>
                                    <span class="min-w-0"
                                        ><span class="text-foreground block text-sm leading-5 font-semibold">{{ t("labels.payment.full") }}</span
                                        ><span class="text-muted-foreground mt-1 block text-xs leading-4">{{
                                            t("labels.payment.full_description", { amount: formatCurrency(grandTotal) })
                                        }}</span></span
                                    >
                                </label>

                                <label
                                    class="flex min-w-0 items-start gap-3 rounded-xl border p-3 transition-colors"
                                    :class="[
                                        selectedPaymentOption === 'installment' ? 'border-primary bg-primary/5' : 'border-border bg-background',
                                        !isInstallmentEligible
                                            ? 'cursor-not-allowed opacity-60'
                                            : 'hover:border-primary/50 hover:bg-secondary/40 cursor-pointer',
                                    ]"
                                >
                                    <FormRadio
                                        type="radio"
                                        name="checkout_payment_method"
                                        value="installment"
                                        v-model="selectedPaymentOption"
                                        :disabled="!isInstallmentEligible"
                                        class="mt-0.5 h-5 w-5"
                                    />
                                    <span class="bg-secondary text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                        ><CalendarDays class="h-4 w-4" aria-hidden="true"
                                    /></span>
                                    <div class="min-w-0">
                                        <span class="text-foreground block text-sm leading-5 font-semibold">{{
                                            t("labels.payment.installment")
                                        }}</span>
                                        <span class="text-muted-foreground mt-1 block text-xs leading-4">
                                            {{
                                                isInstallmentEligible
                                                    ? t("labels.payment.installment_description")
                                                    : t("labels.payment.installment_minimum", { amount: formatCurrency(installmentMinOrderAmount) })
                                            }}
                                        </span>
                                    </div>
                                </label>
                            </div>

                            <p
                                v-if="isOverLimit"
                                class="border-destructive/20 bg-destructive/5 text-destructive mt-3 rounded-xl border p-3 text-xs"
                                role="alert"
                            >
                                ⚠️ Total {{ selectedPaymentType === "installment" ? "cicilan" : "pembelian" }} ({{
                                    formatCurrency(selectedPaymentType === "installment" ? selectedInstallmentPlan?.total_amount : grandTotal)
                                }}) melebihi sisa limit kredit
                            </p>

                            <!-- Installment Calculator -->
                            <div v-if="selectedPaymentType === 'installment'" class="mt-5 space-y-4">
                                <!-- Loading State -->
                                <div
                                    v-if="isLoadingInstallment"
                                    class="bg-secondary/60 flex items-center justify-center gap-2 rounded-xl py-5"
                                    role="status"
                                    aria-live="polite"
                                >
                                    <Loader2 class="text-primary h-5 w-5 animate-spin" aria-hidden="true" />
                                    <span class="text-muted-foreground text-sm">{{ t("labels.checkout.installment_loading") }}</span>
                                </div>

                                <!-- Tenor Selection -->
                                <div v-else-if="installmentCalculations" class="space-y-2">
                                    <label class="text-sm font-semibold">{{ t("labels.checkout.select_tenor") }}</label>
                                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-4">
                                        <Button
                                            v-for="plan in installmentCalculations.plans"
                                            :key="plan.id"
                                            @click="selectedInstallmentPlan = plan"
                                            variant="outline"
                                            type="button"
                                            :aria-pressed="selectedInstallmentPlan?.id === plan.id"
                                            class="min-h-20 w-full flex-col items-start justify-center p-3 text-left lg:min-h-0 lg:p-2.5"
                                            :class="
                                                selectedInstallmentPlan?.id === plan.id
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-primary/50 hover:bg-secondary/40'
                                            "
                                        >
                                            <span class="block text-lg font-bold">{{ plan.tenor }}x</span>
                                            <span class="text-muted-foreground text-xs">{{ plan.fee_percentage }}% fee</span>
                                            <span class="text-primary mt-1 block text-xs font-semibold sm:text-sm">
                                                {{ formatCurrency(plan.monthly_amount) }}/bulan
                                            </span>
                                        </Button>
                                    </div>
                                </div>

                                <!-- Selected Plan Summary -->
                                <div v-if="selectedInstallmentPlan" class="bg-secondary/60 rounded-xl p-4">
                                    <h4 class="text-foreground mb-3 text-sm font-semibold">{{ t("labels.checkout.installment_summary") }}</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span>{{ t("labels.checkout.product_price") }}</span>
                                            <span class="font-semibold">{{ formatCurrency(installmentCalculations.principal_amount) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>{{ t("labels.checkout.fee", { percentage: selectedInstallmentPlan.fee_percentage }) }}</span>
                                            <span class="text-primary font-semibold">{{ formatCurrency(selectedInstallmentPlan.fee_amount) }}</span>
                                        </div>
                                        <div class="border-border flex justify-between border-t pt-2">
                                            <span>{{ t("labels.checkout.installment_total") }}</span>
                                            <span class="font-bold">{{ formatCurrency(selectedInstallmentPlan.total_amount) }}</span>
                                        </div>
                                        <div class="flex justify-between text-lg">
                                            <span>{{ t("labels.checkout.monthly_payment") }}</span>
                                            <span class="text-primary font-bold"
                                                >{{ selectedInstallmentPlan.tenor }}x
                                                {{ formatCurrency(selectedInstallmentPlan.monthly_amount) }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="paymentError" class="text-destructive mt-3 text-xs" role="alert">{{ paymentError }}</p>
                    </Card>

                    <!-- Notes Section -->
                    <Card as="section" class="order-4 rounded-2xl p-4 sm:p-5 md:p-6">
                        <div class="mb-4 flex items-start gap-3">
                            <span
                                class="bg-primary text-primary-foreground flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                                >4</span
                            >
                            <div>
                                <h2 class="text-foreground text-base font-bold sm:text-lg">{{ t("labels.checkout.order_notes") }}</h2>
                                <p class="text-muted-foreground mt-0.5 text-xs">{{ t("labels.checkout.notes_description") }}</p>
                            </div>
                        </div>
                        <FormTextarea
                            v-model="form.notes"
                            rows="3"
                            id="order-notes"
                            class="bg-background"
                            :aria-label="t('labels.checkout.order_notes')"
                            :placeholder="t('placeholders.order_notes')"
                        />
                    </Card>
                </div>

                <!-- Right Side: Order Summary -->
                <aside class="order-2 min-w-0 lg:order-2 lg:col-span-4">
                    <Card as="section" class="rounded-2xl p-4 sm:p-5 md:p-6 lg:sticky lg:top-6">
                        <div class="border-border flex items-start justify-between gap-3 border-b pb-4">
                            <div>
                                <h2 class="text-foreground text-base font-bold sm:text-lg">{{ t("labels.checkout.order_summary") }}</h2>
                                <p class="text-muted-foreground mt-0.5 text-xs">{{ t("labels.checkout.product_count", { count: items.length }) }}</p>
                            </div>
                            <ShieldCheck class="text-primary h-5 w-5 shrink-0" aria-hidden="true" />
                        </div>
                        <div class="mt-4 flex flex-col gap-4">
                            <!-- Pending Vouchers Section -->
                            <div v-if="hasAnyVoucher || isValidatingVouchers" class="order-4 space-y-3 lg:order-3">
                                <div class="mb-2 flex items-center gap-2">
                                    <Ticket class="text-primary h-4 w-4" aria-hidden="true" />
                                    <span class="text-foreground text-xs font-semibold">{{ t("labels.voucher.selected") }}</span>
                                </div>

                                <!-- Shipping Voucher -->
                                <div
                                    v-if="localValidatedVouchers?.shipping"
                                    class="rounded-lg border p-3"
                                    :class="
                                        localValidatedVouchers.shipping.valid
                                            ? 'border-emerald-200 bg-emerald-50'
                                            : 'border-destructive/20 bg-destructive/5'
                                    "
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-start gap-2">
                                            <Truck class="text-primary mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                            <div>
                                                <p class="text-foreground truncate text-xs font-semibold">
                                                    {{ localValidatedVouchers.shipping.name || localValidatedVouchers.shipping.code }}
                                                </p>
                                                <p v-if="localValidatedVouchers.shipping.valid" class="text-xs text-emerald-700">
                                                    {{
                                                        t("labels.voucher.save_amount", {
                                                            amount: localValidatedVouchers.shipping.formatted_discount,
                                                        })
                                                    }}
                                                </p>
                                                <p v-else class="text-destructive flex items-center gap-1 text-xs">
                                                    <AlertCircle class="h-3 w-3" aria-hidden="true" />
                                                    {{ localValidatedVouchers.shipping.error }}
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            :aria-label="t('labels.actions.delete')"
                                            @click="removeVoucher('shipping')"
                                            class="text-muted-foreground hover:text-destructive h-7 w-7 shrink-0"
                                        >
                                            <X class="h-4 w-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Product Voucher -->
                                <div
                                    v-if="localValidatedVouchers?.product"
                                    class="rounded-lg border p-3"
                                    :class="
                                        localValidatedVouchers.product.valid
                                            ? 'border-emerald-200 bg-emerald-50'
                                            : 'border-destructive/20 bg-destructive/5'
                                    "
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-start gap-2">
                                            <Tag class="text-primary mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                            <div class="min-w-0">
                                                <p class="text-foreground truncate text-xs font-semibold">
                                                    {{ localValidatedVouchers.product.name || localValidatedVouchers.product.code }}
                                                </p>
                                                <p v-if="localValidatedVouchers.product.valid" class="text-xs text-emerald-700">
                                                    {{
                                                        t("labels.voucher.save_amount", { amount: localValidatedVouchers.product.formatted_discount })
                                                    }}
                                                </p>
                                                <p v-else class="text-destructive flex items-center gap-1 text-xs">
                                                    <AlertCircle class="h-3 w-3" aria-hidden="true" />
                                                    {{ localValidatedVouchers.product.error }}
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            :aria-label="t('labels.actions.delete')"
                                            @click="removeVoucher('product')"
                                            class="text-muted-foreground hover:text-destructive h-7 w-7 shrink-0"
                                        >
                                            <X class="h-4 w-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Validating State -->
                                <div
                                    v-if="isValidatingVouchers"
                                    class="bg-secondary/60 flex items-center justify-center gap-2 rounded-lg py-2"
                                    role="status"
                                    aria-live="polite"
                                >
                                    <Loader2 class="text-primary h-4 w-4 animate-spin" aria-hidden="true" />
                                    <span class="text-muted-foreground text-xs">Memvalidasi voucher...</span>
                                </div>
                            </div>

                            <!-- Voucher Input -->
                            <div class="border-border order-2 rounded-xl border border-dashed p-4 lg:order-1">
                                <div class="mb-2 flex items-center gap-2">
                                    <Ticket class="text-primary h-4 w-4" aria-hidden="true" />
                                    <span class="text-foreground text-xs font-semibold">{{ t("labels.voucher.enter_code") }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <FormInput
                                        v-model="voucherCode"
                                        type="text"
                                        :placeholder="t('placeholders.voucher_code')"
                                        wrapper-class="min-w-0 flex-1"
                                        class="bg-background px-3 text-xs uppercase"
                                        @keyup.enter="applyVoucher"
                                    />
                                    <Button
                                        type="button"
                                        variant="primary"
                                        size="sm"
                                        @click="applyVoucher"
                                        :disabled="!voucherCode.trim() || isApplyingVoucher"
                                        class="shrink-0"
                                    >
                                        <Loader2 v-if="isApplyingVoucher" class="h-3 w-3 animate-spin" aria-hidden="true" />
                                        <span>{{ t("labels.actions.apply") }}</span>
                                    </Button>
                                </div>
                                <p v-if="voucherError" class="text-destructive mt-2 text-xs" role="alert">{{ voucherError }}</p>

                                <Link
                                    :href="route('frontend.vouchers')"
                                    class="border-border text-muted-foreground hover:border-primary hover:text-primary mt-3 flex items-center justify-between gap-2 rounded-lg border border-dashed px-3 py-2.5 text-xs font-semibold transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <Ticket class="h-4 w-4" aria-hidden="true" />
                                        <span>{{ t("labels.actions.voucher_list") }}</span>
                                    </span>
                                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                                </Link>
                            </div>

                            <!-- List Voucher Link -->
                            <!-- Order Items (Mini list) -->
                            <div class="divide-border order-1 divide-y lg:order-4 lg:max-h-[28rem] lg:overflow-y-auto lg:pr-1">
                                <div v-for="item in items" :key="item.id" class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                                    <div class="bg-secondary h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl sm:h-20 sm:w-20">
                                        <img
                                            :src="item.product?.thumbnail || '/images/placeholders/product-snapshot.svg'"
                                            :alt="item.product?.name || ''"
                                            class="h-full w-full object-cover"
                                            @error="imageFallback"
                                        />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-foreground line-clamp-2 text-xs leading-4 font-bold sm:text-sm">{{ item.product?.name }}</h4>
                                        <p class="text-muted-foreground mt-1 text-xs">
                                            <template v-if="item.product_variant?.variant_name"> {{ item.product_variant.variant_name }} · </template>
                                            {{ t("labels.checkout.quantity", { count: item.quantity }) }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                            <p class="text-primary text-sm font-bold">{{ formatCurrency(item.price) }}</p>
                                            <p
                                                v-if="item.original_price && item.original_price > item.price"
                                                class="text-muted-foreground text-xs line-through"
                                            >
                                                {{ formatCurrency(item.original_price) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-border order-5 space-y-3 border-t pt-4">
                                <div class="flex justify-between gap-4 text-sm">
                                    <span class="text-muted-foreground">{{ t("labels.checkout.subtotal") }}</span>
                                    <span class="text-foreground font-semibold">{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between gap-4 text-sm">
                                    <span class="text-muted-foreground">{{ t("labels.checkout.shipping") }}</span>
                                    <span class="text-foreground text-right font-semibold">
                                        {{ formatCurrency(discountedShippingFee) }}
                                        <span v-if="shippingDiscount > 0" class="ml-1 text-xs text-emerald-600">
                                            (-{{ formatCurrency(shippingDiscount) }})
                                        </span>
                                    </span>
                                </div>
                                <div v-if="saleProductDiscount > 0" class="flex justify-between gap-4 text-sm text-emerald-600">
                                    <span>{{ t("labels.checkout.product_discount") }}</span>
                                    <span class="font-semibold">-{{ formatCurrency(saleProductDiscount) }}</span>
                                </div>
                                <div v-if="voucherProductDiscount > 0" class="flex justify-between gap-4 text-sm text-emerald-600">
                                    <span>{{ t("labels.checkout.voucher_discount") }}</span>
                                    <span class="font-semibold">-{{ formatCurrency(voucherProductDiscount) }}</span>
                                </div>
                                <div class="border-border flex items-baseline justify-between gap-4 border-t pt-4">
                                    <span class="text-foreground text-sm font-bold">{{ t("labels.checkout.total") }}</span>
                                    <span class="text-primary text-xl font-bold">{{ formatCurrency(grandTotal) }}</span>
                                </div>
                                <div v-if="totalVoucherDiscount > 0" class="rounded-xl bg-emerald-50 p-3 text-center">
                                    <p class="text-xs font-semibold text-emerald-700">
                                        💰 {{ t("labels.checkout.total_savings", { amount: formatCurrency(totalVoucherDiscount) }) }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="paymentError"
                                class="border-destructive/20 bg-destructive/5 text-destructive order-6 rounded-xl border p-3 text-xs"
                                role="alert"
                            >
                                {{ paymentError }}
                            </p>

                            <Button
                                @click="submitOrder"
                                :disabled="!canSubmitOrder || isProcessingOrder"
                                type="button"
                                class="bg-primary text-primary-foreground hover:bg-primary/90 disabled:bg-muted fixed inset-x-4 bottom-3 z-40 order-7 flex items-center justify-center gap-3 rounded-xl py-3.5 text-sm font-bold shadow-lg transition-all lg:static lg:mt-3 lg:w-full lg:shadow-sm"
                            >
                                <svg v-if="isProcessingOrder" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                    ></path>
                                </svg>
                                <span>{{ isProcessingOrder ? t("labels.actions.processing") : t("labels.actions.place_order") }}</span>
                            </Button>

                            <div class="order-8 hidden pt-2 lg:block">
                                <p
                                    class="text-muted-foreground text-center text-xs leading-relaxed"
                                    v-html="t('labels.checkout.terms_agreement')"
                                ></p>
                            </div>
                        </div>
                    </Card>
                </aside>
            </div>
            <div
                v-if="isAddressModalOpen"
                class="bg-foreground/50 fixed inset-0 z-50 flex items-end sm:items-center sm:justify-center sm:p-6"
                role="presentation"
                @click.self="closeAddressModal"
            >
                <section
                    class="bg-background max-h-[92vh] w-full overflow-y-auto rounded-t-2xl p-6 shadow-2xl sm:max-w-2xl sm:rounded-2xl md:p-8"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="t(editingAddress ? 'labels.address.edit_heading' : 'labels.address.new_heading')"
                >
                    <div class="border-border mb-6 flex items-start justify-between gap-4 border-b pb-4">
                        <div>
                            <h2 class="text-foreground text-lg font-bold">
                                {{ t(editingAddress ? "labels.address.edit_heading" : "labels.address.new_heading") }}
                            </h2>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{ t(editingAddress ? "labels.address.edit_description" : "labels.address.new_description") }}
                            </p>
                        </div>
                        <Button type="button" variant="ghost" size="icon" :aria-label="t('labels.dialogs.cancel')" @click="closeAddressModal"
                            ><X class="h-5 w-5"
                        /></Button>
                    </div>
                    <AddressForm
                        :provinces="provinces"
                        :address="editingAddress"
                        :submit-label="editingAddress ? t('labels.actions.update') : undefined"
                        @saved="addressSaved"
                        @cancel="closeAddressModal"
                    />
                </section>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
