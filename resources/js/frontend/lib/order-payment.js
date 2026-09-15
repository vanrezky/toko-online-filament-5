const ORDER_BILLING_STATUS_CONFIG = {
    not_applicable: {
        labelKey: "labels.order.billing_status.not_applicable",
        colorClass: "bg-[#f5f3fc] text-[#6b5a4d]",
    },
    pending: {
        labelKey: "labels.order.billing_status.pending",
        colorClass: "bg-amber-100 text-amber-800",
    },
    submitted: {
        labelKey: "labels.order.billing_status.submitted",
        colorClass: "bg-sky-100 text-sky-800",
    },
    paid: {
        labelKey: "labels.order.billing_status.paid",
        colorClass: "bg-emerald-100 text-emerald-800",
    },
    failed: {
        labelKey: "labels.order.billing_status.failed",
        colorClass: "bg-red-100 text-red-800",
    },
    cancelled: {
        labelKey: "labels.order.billing_status.cancelled",
        colorClass: "bg-red-100 text-red-800",
    },
};

export const getOrderPaymentLabel = (order, t) => {
    if (order.payment_method === 'midtrans') {
        return 'Midtrans';
    }
    if (order.payment_type === 'balance') {
        return t('labels.payment.store_balance');
    }

    if (order.payment_type === 'installment') {
        if (order.installment_plan) {
            return t('labels.payment.installment_tenor', { tenor: order.installment_plan.tenor });
        }

        return t('labels.payment.installment');
    }

    if (! ['full', 'installment', 'balance'].includes(order.payment_type) && order.payment_method === 'saldo') {
        return t('labels.payment.store_balance');
    }

    return t('labels.payment.full');
};

export const getOrderBillingStatusLabel = (status, t) => {
    const config = ORDER_BILLING_STATUS_CONFIG[status] ?? ORDER_BILLING_STATUS_CONFIG.not_applicable;

    return t(config.labelKey);
};

export const getOrderBillingStatusColor = (status, fallback = "bg-[#f5f3fc] text-[#6b5a4d]") =>
    ORDER_BILLING_STATUS_CONFIG[status]?.colorClass ?? fallback;
