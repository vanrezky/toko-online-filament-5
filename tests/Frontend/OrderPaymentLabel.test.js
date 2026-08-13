import { describe, expect, it } from "vitest";
import { getOrderPaymentLabel } from "../../resources/js/frontend/lib/order-payment";

const t = (key, params = {}) => {
    const labels = {
        'labels.payment.store_balance': 'Store Balance',
        'labels.payment.full': 'Pay in full from credit limit',
        'labels.payment.installment': 'Pay in installments from credit limit',
        'labels.payment.installment_tenor': `Installment ${params.tenor}x`,
    };

    return labels[key];
};

describe('order payment labels', () => {
    it('shows store balance for a balance payment type', () => {
        expect(getOrderPaymentLabel({ payment_type: 'balance' }, t)).toBe('Store Balance');
    });

    it('shows store balance for the persisted saldo payment method', () => {
        expect(getOrderPaymentLabel({ payment_method: 'saldo' }, t)).toBe('Store Balance');
    });

    it('prioritizes a recognized payment type over a conflicting payment method', () => {
        expect(getOrderPaymentLabel({ payment_type: 'full', payment_method: 'saldo' }, t)).toBe('Pay in full from credit limit');
    });

    it('keeps an installment label with its tenor', () => {
        expect(getOrderPaymentLabel({ payment_type: 'installment', installment_plan: { tenor: 6 } }, t)).toBe('Installment 6x');
    });

    it('falls back to the full credit-limit label for other payment representations', () => {
        expect(getOrderPaymentLabel({ payment_type: 'full' }, t)).toBe('Pay in full from credit limit');
    });
});
