## Why

Checkout currently presents installment, balance, and full-payment methods as one undifferentiated list. Customers need a clear distinction between methods they can use now and future payment gateways that are temporarily unavailable.

## What Changes

- Group currently active checkout payment methods under a clear available-methods section.
- Add a separate Payment Gateway section for BCA, BRI, Credit Card, and ShopeePay.
- Present every gateway as disabled with a maintenance notice, without changing the selected payment method or checkout payload.
- Preserve the current installment, balance, and full-payment behavior.

## Capabilities

### New Capabilities

- `checkout-payment-method-grouping`: Clearly distinguish available checkout payment methods from disabled, future gateway options.

### Modified Capabilities

- None.

## Impact

- Frontend: checkout payment-method UI and Indonesian/English locale strings.
- No backend payment logic, transaction payload, validation rule, or payment-gateway integration changes.
