## Why

Issue #25 reports that full credit-limit and installment checkout fail when no external payment gateway is active. Both are internal credit facilities, so gateway availability must not block the core checkout flow.

## What Changes

- Make full credit-limit and installment checkout complete independently of external gateway configuration.
- Preserve existing billing, due-date, installment, voucher, shipping, flash-sale reservation, cart completion, and notification behavior.
- Keep balance checkout as its existing internal ledger-debit flow and preserve its gateway bypass.
- Add regression coverage for all internal payment types when no gateway is active.

## Capabilities

### New Capabilities

- `credit-checkout-gateway-independence`: Complete internal credit-limit checkout without creating an external gateway payment.

### Modified Capabilities

- `checkout-payment-method-grouping`: Preserve the unavailable-gateway contract while full credit-limit and installment remain usable internal methods.

## Impact

- `app/Http/Controllers/Frontend/CheckoutController.php`
- `tests/Feature/CheckoutFullPaymentCreditLimitTest.php`
- Checkout payment-method OpenSpec requirements
- No payment provider, schema, API payload, or checkout UI changes
