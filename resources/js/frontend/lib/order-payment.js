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
