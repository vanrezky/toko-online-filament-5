## Context

The order-detail page currently maps installment orders to their tenor and maps every other transaction to the full credit-limit label. Checkout persists balance orders with both `payment_type=balance` and `payment_method=saldo`.

## Goals / Non-Goals

**Goals:**

- Resolve customer and admin payment labels from persisted transaction payment data.
- Keep full credit-limit and installment labels unchanged.
- Cover the mapping with a focused frontend unit test.

**Non-Goals:**

- Change checkout, balance debits/refunds, billing records, payment gateways, or the Balance Filament form.

## Decisions

- Extract a small order-payment label resolver into the frontend library so `Show.vue` remains a view and all persisted-field fallback behavior is directly testable.
- Treat either `payment_type=balance` or `payment_method=saldo` as a store-balance payment to support existing persisted transaction representations. Payment type remains the primary source when it is present.
- Use a dedicated localized display label instead of the existing balance-selection CTA text.
- Centralize admin payment-type labels in `TransactionResource`, then use the same mapping in the read-only edit form and the transaction view infolist so both surfaces expose balance payments without changing transaction data.

## Risks / Trade-offs

- [Older transaction data has incomplete payment fields] → Preserve the existing full-credit fallback for unknown values.
- [Payment-method aliases expand later] → Keep the resolver narrow to the currently persisted `saldo` alias.
