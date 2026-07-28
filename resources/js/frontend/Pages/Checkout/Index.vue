<script setup>
import Button from "@frontend/components/UI/Button.vue";
import { ref, computed, watch, onMounted } from "vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { Loader2, Ticket, X, Truck, Tag, AlertCircle } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { installmentService } from "../../services/installmentService";
import TemplateWrapper from "../../components/TemplateWrapper.vue";
import PageShell from "../../components/PageShell.vue";
import FormInput from "../../components/UI/FormInput.vue";
import FormRadio from "../../components/UI/FormRadio.vue";
import FormTextarea from "../../components/UI/FormTextarea.vue";
import Card from "../../components/UI/Card.vue";
import { formatCurrency } from "../../lib/utils";
import { createLatestRequestGate, reconcileShippingMethods } from "../../lib/shippingMethods";

const props = defineProps({
    cart: Object,
    addresses: Array,
    shippingMethods: Array,
    pendingVouchers: Object,
    validatedVouchers: Object,
    activeGateway: String,
    installmentPlans: Array,
    creditLimit: Object,
    installmentMinOrderAmount: Number,
    balance: Object,
});

const { t } = useI18n();

const form = useForm({
    address_id: props.addresses?.find((a) => a.is_featured)?.id || props.addresses?.[0]?.id || null,
    shipping_methods: {},
    payment_method: props.activeGateway === "midtrans" ? "midtrans" : "bank_transfer",
    notes: "",
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

const selectedPaymentType = ref("full");
const selectedInstallmentPlan = ref(null);
const installmentCalculations = ref(null);
const isLoadingInstallment = ref(false);
const creditLimitRemaining = ref(props.creditLimit?.remaining || 0);
const balanceAvailable = computed(() => Number(props.balance?.available || 0));
const isBalanceEnabled = computed(() => props.balance?.enabled === true);
const isBalanceSufficient = computed(() => balanceAvailable.value >= grandTotal.value);
const isCreditLimitEnforced = computed(() => props.creditLimit?.enforced !== false);
const installmentMinOrderAmount = computed(() => Number(props.installmentMinOrderAmount || 1000000));
const unavailablePaymentGateways = ["BCA", "BRI", "Credit Card", "ShopeePay"];

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
            params: { address_id: form.address_id },
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

const items = computed(() => props.cart?.items || []);
const subtotal = computed(() => {
    return items.value.reduce((total, item) => total + (item.original_price || item.price) * item.quantity, 0);
});

const saleProductDiscount = computed(() => {
    return items.value.reduce((total, item) => total + (item.discount || 0) * item.quantity, 0);
});

const shippingDiscount = computed(() => {
    return localValidatedVouchers.value?.shipping?.discount_amount || 0;
});

const voucherProductDiscount = computed(() => {
    return localValidatedVouchers.value?.product?.discount_amount || 0;
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
    return Object.values(form.shipping_methods).reduce((sum, method) => sum + (method.price || 0), 0);
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
    if (type === "full") {
        selectedInstallmentPlan.value = null;
    }
};

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
    if (selectedPaymentType.value === "full") {
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
                const isProduction = payment.payment_url && payment.payment_url.includes("app.midtrans.com");
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
        <PageShell container :title="t('labels.checkout.heading')">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-12">
                <!-- Left Side: Forms -->
                <div class="flex flex-col gap-6 lg:col-span-7">
                    <!-- Shipping Address Section -->
                    <Card as="section" class="order-2 rounded-xl p-6 md:p-8">
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fa8456] text-sm font-bold text-white">2</div>
                                <h2 class="text-base font-bold text-[#2d1b0e]">{{ t("labels.checkout.shipping_address") }}</h2>
                            </div>
                            <Link
                                :href="route('frontend.account', { section: 'addresses' })"
                                class="text-xs font-semibold text-[#fa8456] transition-colors hover:text-[#e56f3f]"
                                >{{ t("labels.actions.manage") }}</Link
                            >
                        </div>

                        <div
                            v-if="isPickupOnlySelection"
                            class="rounded-xl border border-[#dbeafe] bg-[#eff6ff] p-5 text-sm leading-relaxed text-[#1e3a8a]"
                        >
                            {{ t("labels.checkout.pickup_no_address_required") }}
                        </div>

                        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                v-for="address in addresses"
                                :key="address.id"
                                @click="form.address_id = address.id"
                                class="relative cursor-pointer rounded-xl border p-5 transition-all"
                                :class="
                                    form.address_id === address.id
                                        ? 'border-[#fa8456] bg-[#fff5f0] ring-2 ring-[#fa8456]/20'
                                        : 'border-[#e8e6ef] bg-[#fafafa] hover:border-[#fa8456]/50'
                                "
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-[#2d1b0e]">{{ address.name }}</h3>
                                        <p class="mt-1 text-xs text-[#6b5a4d]">{{ address.phone }}</p>
                                    </div>
                                    <svg class="h-4 w-4 text-[#fa8456]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <p class="mt-3 text-xs leading-relaxed text-[#6b5a4d]">
                                    {{ address.address }}<br />
                                    {{ address.sub_district_name }}, {{ address.district_name }}<br />
                                    {{ address.province_name }} {{ address.postal_code }}
                                </p>
                                <div v-if="form.address_id === address.id" class="absolute -top-1.5 -right-1.5">
                                    <svg class="h-5 w-5 text-[#fa8456] drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.address_id && !isPickupOnlySelection" class="mt-3 text-xs text-red-500">{{ form.errors.address_id }}</p>
                    </Card>

                    <!-- Shipping Method Section -->
                    <section class="order-1 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fa8456] text-sm font-bold text-white">1</div>
                                <h2 class="text-base font-bold text-[#2d1b0e]">{{ t("labels.checkout.shipping_method") }}</h2>
                            </div>
                            <div v-if="isLoadingShipping" class="flex items-center gap-2">
                                <Loader2 class="h-4 w-4 animate-spin text-[#fa8456]" />
                                <span class="text-xs font-semibold text-[#6b5a4d]">{{ t("labels.actions.updating") }}</span>
                            </div>
                        </div>

                        <div v-if="isLoadingShipping" class="animate-pulse space-y-6">
                            <div v-for="i in 2" :key="i" class="space-y-4">
                                <div class="h-4 w-1/3 rounded bg-gray-200"></div>
                                <div class="space-y-2">
                                    <div v-for="j in 2" :key="j" class="flex items-center justify-between rounded-xl border border-[#e8e6ef] p-4">
                                        <div class="flex items-center gap-4">
                                            <div class="h-5 w-5 rounded-full bg-gray-200"></div>
                                            <div>
                                                <div class="mb-2 h-4 w-24 rounded bg-gray-200"></div>
                                                <div class="h-3 w-32 rounded bg-gray-200"></div>
                                            </div>
                                        </div>
                                        <div class="h-4 w-16 rounded bg-gray-200"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="shippingResults.length > 0" class="space-y-6">
                            <div v-for="warehouse in shippingResults" :key="warehouse.warehouse_id" class="space-y-4">
                                <h3 class="border-b border-[#f0eef5] pb-2 text-xs font-semibold text-[#6b5a4d]">
                                    {{ t("labels.checkout.shipped_from", { warehouse: warehouse.warehouse_name }) }}
                                </h3>
                                <div class="space-y-2">
                                    <label
                                        v-for="option in warehouse.options"
                                        :key="option.courier_code"
                                        class="flex cursor-pointer items-center justify-between rounded-xl border p-4 transition-all hover:bg-[#fafafa]"
                                        :class="
                                            form.shipping_methods[warehouse.warehouse_id]?.courier_code === option.courier_code
                                                ? 'border-[#fa8456] bg-[#fff5f0]'
                                                : 'border-[#e8e6ef]'
                                        "
                                    >
                                        <div class="flex items-center">
                                            <div class="relative flex items-center justify-center">
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
                                                    class="h-5 w-5 border-[#e8e6ef] text-[#fa8456] accent-[#fa8456]"
                                                />
                                            </div>
                                            <div class="ml-4">
                                                <span class="block text-sm font-bold text-[#2d1b0e]">{{ option.courier_name }}</span>
                                                <span class="text-xs text-[#6b5a4d]">{{
                                                    t("labels.checkout.estimation", { estimation: option.estimation })
                                                }}</span>
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold text-[#fa8456]">{{ formatCurrency(option.price) }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else-if="!isLoadingShipping && form.address_id"
                            class="rounded-xl border border-dashed border-[#e8e6ef] p-8 text-center"
                        >
                            <p class="text-xs text-[#6b5a4d]">{{ t("messages.error.no_shipping_options") }}</p>
                        </div>
                        <div v-else-if="!form.address_id" class="rounded-xl border border-dashed border-[#e8e6ef] p-8 text-center">
                            <p class="text-xs text-[#6b5a4d]">{{ t("messages.error.select_address_first") }}</p>
                        </div>
                        <p v-if="form.errors.shipping_methods" class="mt-3 text-xs text-red-500">{{ form.errors.shipping_methods }}</p>
                    </section>

                    <!-- Payment Method Section -->
                    <section v-if="activeGateway !== 'midtrans' || isBalanceEnabled" class="order-3 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fa8456] text-sm font-bold text-white">3</div>
                            <h2 class="text-base font-bold text-[#2d1b0e]">{{ t("labels.checkout.payment_method") }}</h2>
                        </div>

                        <h3 class="mb-3 text-sm font-semibold text-[#2d1b0e]">{{ t("labels.payment.available_methods") }}</h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label
                                v-if="isBalanceEnabled"
                                class="flex items-center rounded-xl border p-4 transition-all"
                                :class="[
                                    selectedPaymentType === 'balance' ? 'border-[#fa8456] bg-[#fff5f0]' : 'border-[#e8e6ef]',
                                    !isBalanceSufficient ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-[#fafafa]',
                                ]"
                            >
                                <FormRadio
                                    type="radio"
                                    value="balance"
                                    v-model="selectedPaymentType"
                                    :disabled="!isBalanceSufficient"
                                    class="h-5 w-5 border-[#e8e6ef] text-[#fa8456] accent-[#fa8456]"
                                />
                                <span class="ml-3 text-sm font-semibold text-[#2d1b0e]">{{ t("labels.payment.balance") }}</span>
                                <span class="ml-auto text-xs text-[#6b5a4d]">{{ formatCurrency(balanceAvailable) }}</span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center rounded-xl border p-4 transition-all hover:bg-[#fafafa]"
                                :class="selectedPaymentType === 'full' ? 'border-[#fa8456] bg-[#fff5f0]' : 'border-[#e8e6ef]'"
                            >
                                <FormRadio
                                    type="radio"
                                    value="full"
                                    v-model="selectedPaymentType"
                                    @change="selectPaymentType('full')"
                                    class="h-5 w-5 border-[#e8e6ef] text-[#fa8456] accent-[#fa8456]"
                                />
                                <span class="ml-3 text-sm font-semibold text-[#2d1b0e]">{{ t("labels.payment.full") }}</span>
                            </label>

                            <label
                                class="flex cursor-pointer items-center rounded-xl border p-4 transition-all hover:bg-[#fafafa]"
                                :class="[
                                    selectedPaymentType === 'installment' ? 'border-[#fa8456] bg-[#fff5f0]' : 'border-[#e8e6ef]',
                                    !isInstallmentEligible ? 'cursor-not-allowed opacity-60 hover:bg-white' : '',
                                ]"
                            >
                                <FormRadio
                                    type="radio"
                                    value="installment"
                                    v-model="selectedPaymentType"
                                    @change="selectPaymentType('installment')"
                                    :disabled="!isInstallmentEligible"
                                    class="h-5 w-5 border-[#e8e6ef] text-[#fa8456] accent-[#fa8456]"
                                />
                                <span class="ml-3 text-sm font-semibold text-[#2d1b0e]">{{ t("labels.payment.installment") }}</span>
                            </label>
                        </div>

                        <div class="mt-7 border-t border-[#e8e6ef] pt-6">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-[#2d1b0e]">{{ t("labels.payment.gateway") }}</h3>
                                <span class="rounded-full bg-[#f8f7fc] px-2.5 py-1 text-xs font-medium text-[#6b5a4d]">
                                    {{ t("labels.payment.maintenance") }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    v-for="gateway in unavailablePaymentGateways"
                                    :key="gateway"
                                    type="button"
                                    disabled
                                    class="flex cursor-not-allowed items-center justify-between rounded-xl border border-[#e8e6ef] bg-[#f8f7fc] p-4 text-left opacity-70"
                                >
                                    <span class="text-sm font-semibold text-[#6b5a4d]">{{ gateway }}</span>
                                    <span class="text-xs text-[#6b5a4d]">{{ t("labels.payment.maintenance") }}</span>
                                </button>
                            </div>
                        </div>

                        <p v-if="isBalanceEnabled && !isBalanceSufficient" class="mt-3 text-xs text-amber-700">
                            {{ t("labels.payment.balance_insufficient") }}
                        </p>

                        <p v-if="!isInstallmentEligible" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                            Cicilan tersedia untuk total belanja minimal {{ formatCurrency(installmentMinOrderAmount) }}. Pesanan di bawah nominal
                            tersebut wajib bayar penuh melalui potongan payroll.
                        </p>

                        <!-- Installment Calculator -->
                        <div v-if="selectedPaymentType === 'installment'" class="mt-4 space-y-4">
                            <!-- Loading State -->
                            <div v-if="isLoadingInstallment" class="flex items-center justify-center py-4">
                                <Loader2 class="h-5 w-5 animate-spin text-[#fa8456]" />
                                <span class="ml-2 text-sm text-[#6b5a4d]">Memuat tenor cicilan...</span>
                            </div>

                            <!-- Tenor Selection -->
                            <div v-else-if="installmentCalculations" class="space-y-2">
                                <label class="text-sm font-semibold">{{ t("labels.checkout.select_tenor") }}</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <Button
                                        v-for="plan in installmentCalculations.plans"
                                        :key="plan.id"
                                        @click="selectedInstallmentPlan = plan"
                                        class="rounded-lg border p-3 text-center transition-all"
                                        :class="
                                            selectedInstallmentPlan?.id === plan.id
                                                ? 'border-[#fa8456] bg-[#fff5f0]'
                                                : 'border-[#e8e6ef] hover:border-[#fa8456]/50'
                                        "
                                    >
                                        <span class="block text-lg font-bold">{{ plan.tenor }}x</span>
                                        <span class="text-xs text-[#6b5a4d]">{{ plan.fee_percentage }}% fee</span>
                                        <span class="mt-1 block text-sm font-semibold text-[#fa8456]">
                                            {{ formatCurrency(plan.monthly_amount) }}/bulan
                                        </span>
                                    </Button>
                                </div>
                            </div>

                            <!-- Selected Plan Summary -->
                            <div v-if="selectedInstallmentPlan" class="rounded-xl bg-[#f8f7fc] p-4">
                                <h4 class="mb-3 text-sm font-semibold">{{ t("labels.checkout.installment_summary") }}</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>Harga Produk</span>
                                        <span class="font-semibold">{{ formatCurrency(installmentCalculations.principal_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Fee ({{ selectedInstallmentPlan.fee_percentage }}%)</span>
                                        <span class="font-semibold text-[#fa8456]">{{ formatCurrency(selectedInstallmentPlan.fee_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-[#e8e6ef] pt-2">
                                        <span>Total Cicilan</span>
                                        <span class="font-bold">{{ formatCurrency(selectedInstallmentPlan.total_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-lg">
                                        <span>Angsuran/bulan</span>
                                        <span class="font-bold text-[#fa8456]"
                                            >{{ selectedInstallmentPlan.tenor }}x {{ formatCurrency(selectedInstallmentPlan.monthly_amount) }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Limit Info - Show for both payment types -->
                        <div v-if="isCreditLimitEnforced" class="mt-4 rounded-lg border border-[#e8e6ef] p-4">
                            <div class="flex justify-between text-sm">
                                <span>Sisa Limit Kredit</span>
                                <span class="font-semibold" :class="isOverLimit ? 'text-red-500' : 'text-green-600'">
                                    {{ formatCurrency(creditLimitRemaining) }}
                                </span>
                            </div>
                            <p v-if="isOverLimit" class="mt-2 text-xs text-red-500">
                                ⚠️ Total {{ selectedPaymentType === "installment" ? "cicilan" : "pembelian" }} ({{
                                    formatCurrency(selectedPaymentType === "installment" ? selectedInstallmentPlan?.total_amount : grandTotal)
                                }}) melebihi sisa limit kredit
                            </p>
                        </div>

                        <p v-if="paymentError" class="mt-3 text-xs text-red-500">{{ paymentError }}</p>
                    </section>

                    <!-- Notes Section -->
                    <section class="order-4 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                        <h2 class="mb-4 text-sm font-bold text-[#2d1b0e]">{{ t("labels.checkout.order_notes") }}</h2>
                        <FormTextarea
                            v-model="form.notes"
                            rows="3"
                            class="border-[#e8e6ef] bg-white p-4"
                            :placeholder="t('placeholders.order_notes')"
                        />
                    </section>
                </div>

                <!-- Right Side: Order Summary -->
                <div class="lg:col-span-5">
                    <div class="sticky top-32 space-y-6 rounded-xl border border-[#e8e6ef] bg-white p-6 shadow-sm md:p-8">
                        <h2 class="border-b border-[#f0eef5] pb-4 text-sm font-bold text-[#2d1b0e]">{{ t("labels.checkout.order_summary") }}</h2>

                        <!-- Pending Vouchers Section -->
                        <div v-if="hasAnyVoucher || isValidatingVouchers" class="space-y-3">
                            <div class="mb-2 flex items-center gap-2">
                                <Ticket class="h-4 w-4 text-[#fa8456]" />
                                <span class="text-xs font-semibold text-[#2d1b0e]">{{ t("labels.voucher.selected") }}</span>
                            </div>

                            <!-- Shipping Voucher -->
                            <div
                                v-if="localValidatedVouchers?.shipping"
                                class="rounded-lg border p-3"
                                :class="localValidatedVouchers.shipping.valid ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                            <Truck class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-[#2d1b0e]">
                                                {{ localValidatedVouchers.shipping.name || localValidatedVouchers.shipping.code }}
                                            </p>
                                            <p v-if="localValidatedVouchers.shipping.valid" class="text-xs text-green-600">
                                                {{ t("labels.voucher.save_amount", { amount: localValidatedVouchers.shipping.formatted_discount }) }}
                                            </p>
                                            <p v-else class="flex items-center gap-1 text-xs text-red-600">
                                                <AlertCircle class="h-3 w-3" />
                                                {{ localValidatedVouchers.shipping.error }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button @click="removeVoucher('shipping')" class="text-gray-400 hover:text-red-500">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Product Voucher -->
                            <div
                                v-if="localValidatedVouchers?.product"
                                class="rounded-lg border p-3"
                                :class="localValidatedVouchers.product.valid ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                            <Tag class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-[#2d1b0e]">
                                                {{ localValidatedVouchers.product.name || localValidatedVouchers.product.code }}
                                            </p>
                                            <p v-if="localValidatedVouchers.product.valid" class="text-xs text-green-600">
                                                {{ t("labels.voucher.save_amount", { amount: localValidatedVouchers.product.formatted_discount }) }}
                                            </p>
                                            <p v-else class="flex items-center gap-1 text-xs text-red-600">
                                                <AlertCircle class="h-3 w-3" />
                                                {{ localValidatedVouchers.product.error }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button @click="removeVoucher('product')" class="text-gray-400 hover:text-red-500">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Validating State -->
                            <div v-if="isValidatingVouchers" class="flex items-center justify-center gap-2 py-2">
                                <Loader2 class="h-4 w-4 animate-spin text-[#fa8456]" />
                                <span class="text-xs text-[#6b5a4d]">Memvalidasi voucher...</span>
                            </div>
                        </div>

                        <!-- Voucher Input -->
                        <div class="rounded-lg border border-dashed border-[#e8e6ef] p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <Ticket class="h-4 w-4 text-[#fa8456]" />
                                <span class="text-xs font-semibold text-[#2d1b0e]">{{ t("labels.voucher.enter_code") }}</span>
                            </div>
                            <div class="flex gap-2">
                                <FormInput
                                    v-model="voucherCode"
                                    type="text"
                                    :placeholder="t('placeholders.voucher_code')"
                                    wrapper-class="flex-1"
                                    class="border-[#e8e6ef] bg-[#fafafa] px-3 py-2 text-xs uppercase focus-visible:bg-white"
                                    @keyup.enter="applyVoucher"
                                />
                                <Button
                                    @click="applyVoucher"
                                    :disabled="!voucherCode.trim() || isApplyingVoucher"
                                    class="flex items-center justify-center gap-1 rounded-lg bg-[#fa8456] px-4 py-2 text-xs font-semibold text-white transition-all hover:bg-[#e56f3f] disabled:bg-[#c4bfc9]"
                                >
                                    <Loader2 v-if="isApplyingVoucher" class="h-3 w-3 animate-spin" />
                                    <span>{{ t("labels.actions.apply") }}</span>
                                </Button>
                            </div>
                            <p v-if="voucherError" class="mt-2 text-xs text-red-500">{{ voucherError }}</p>
                        </div>

                        <!-- List Voucher Link -->
                        <Link
                            :href="route('frontend.vouchers')"
                            class="flex items-center justify-center gap-2 rounded-lg border border-dashed border-[#e8e6ef] py-3 text-xs font-semibold text-[#6b5a4d] transition-all hover:border-[#fa8456] hover:text-[#fa8456]"
                        >
                            <Ticket class="h-4 w-4" />
                            <span>{{ t("labels.actions.voucher_list") }}</span>
                        </Link>

                        <!-- Order Items (Mini list) -->
                        <div class="max-h-[400px] space-y-4 overflow-y-auto pr-2">
                            <div v-for="item in items" :key="item.id" class="flex items-start gap-4">
                                <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-xl bg-[#f5f3fc]">
                                    <img
                                        :src="item.product?.thumbnail || 'https://placehold.co/100x120/f5f3fc/2d1b0e?text=Produk'"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div class="min-w-0 flex-grow py-1">
                                    <h4 class="truncate text-sm font-bold text-[#2d1b0e]">{{ item.product?.name }}</h4>
                                    <p v-if="item.product_variant" class="mt-0.5 text-xs text-[#6b5a4d]">
                                        {{ item.product_variant.variant_name }}
                                    </p>
                                    <p class="mt-2 text-xs text-[#6b5a4d]">Qty: {{ item.quantity }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <p class="text-sm font-bold text-[#fa8456]">{{ formatCurrency(item.price) }}</p>
                                        <p v-if="item.original_price && item.original_price > item.price" class="text-xs text-[#6b5a4d] line-through">
                                            {{ formatCurrency(item.original_price) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-[#f0eef5] pt-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-[#6b5a4d]">{{ t("labels.checkout.subtotal") }}</span>
                                <span class="font-semibold text-[#2d1b0e]">{{ formatCurrency(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[#6b5a4d]">{{ t("labels.checkout.shipping") }}</span>
                                <span class="font-semibold text-[#2d1b0e]">
                                    {{ formatCurrency(discountedShippingFee) }}
                                    <span v-if="shippingDiscount > 0" class="ml-1 text-xs text-green-600">
                                        (-{{ formatCurrency(shippingDiscount) }})
                                    </span>
                                </span>
                            </div>
                            <div v-if="saleProductDiscount > 0" class="flex justify-between text-sm text-green-600">
                                <span>{{ t("labels.checkout.product_discount") }}</span>
                                <span class="font-semibold">-{{ formatCurrency(saleProductDiscount) }}</span>
                            </div>
                            <div v-if="voucherProductDiscount > 0" class="flex justify-between text-sm text-green-600">
                                <span>{{ t("labels.checkout.voucher_discount") }}</span>
                                <span class="font-semibold">-{{ formatCurrency(voucherProductDiscount) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#e8e6ef] pt-4">
                                <span class="text-sm font-bold text-[#2d1b0e]">{{ t("labels.checkout.total") }}</span>
                                <span class="text-xl font-bold text-[#fa8456]">{{ formatCurrency(grandTotal) }}</span>
                            </div>
                            <div v-if="totalVoucherDiscount > 0" class="rounded-lg bg-green-50 p-3 text-center">
                                <p class="text-sm font-semibold text-green-700">
                                    💰 {{ t("labels.checkout.total_savings", { amount: formatCurrency(totalVoucherDiscount) }) }}
                                </p>
                            </div>
                        </div>

                        <p v-if="paymentError" class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-600">
                            {{ paymentError }}
                        </p>

                        <Button
                            @click="submitOrder"
                            :disabled="!canSubmitOrder || isProcessingOrder"
                            class="flex w-full items-center justify-center gap-3 rounded-full bg-[#fa8456] py-4 text-sm font-bold text-white shadow-md transition-all hover:bg-[#e56f3f] hover:shadow-lg disabled:bg-[#c4bfc9]"
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

                        <div class="pt-2">
                            <p class="text-center text-xs leading-relaxed text-[#6b5a4d]" v-html="t('labels.checkout.terms_agreement')"></p>
                        </div>
                    </div>
                </div>
            </div>
        </PageShell>
    </TemplateWrapper>
</template>
