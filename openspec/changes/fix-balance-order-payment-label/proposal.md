## Why

Issue #24 reports an incorrect payment method on completed balance orders. The order detail currently treats every non-installment order as a full credit-limit payment, so a persisted balance payment is misrepresented to the customer.

## What Changes

- Display a localized store-balance payment label for transactions persisted with `payment_type=balance` or its `payment_method=saldo` representation in both customer order detail and Filament transaction detail.
- Preserve the distinct labels for full credit-limit and installment transactions.
- Add focused frontend regression coverage for payment-label selection.

## Capabilities

### New Capabilities

- `order-payment-method-display`: Display the persisted payment method accurately in customer and admin transaction details.

### Modified Capabilities

- None.

## Impact

- `resources/js/frontend/Pages/Orders/Show.vue`
- `resources/js/locales/id.json` and `resources/js/locales/en.json`
- `app/Filament/Resources/Transactions/TransactionResource.php` and `Pages/ViewTransaction.php`
- Focused frontend tests for the order payment label
