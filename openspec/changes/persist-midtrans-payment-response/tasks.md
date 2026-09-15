## 1. Schema and model

- [x] 1.1 Add a migration for `transaction_payment_responses` with transaction/provider/source uniqueness, channel, and JSON response fields.
- [x] 1.2 Add `TransactionPaymentResponse`, the transaction relation, and source-aware response persistence.

## 2. Gateway and flow integration

- [x] 2.1 Extend `PaymentStatus` with optional metadata.
- [x] 2.2 Return sanitized Midtrans channel/response metadata from webhook and status parsing.
- [x] 2.3 Persist verified webhook metadata without changing existing billing behavior.
- [x] 2.4 Persist payment-status metadata while retaining amount verification and billing transitions.

## 3. Regression coverage

- [x] 3.1 Test Midtrans QRIS webhook metadata and transaction persistence.
- [x] 3.2 Test payment-status response metadata and transaction persistence.
- [x] 3.3 Test that invalid webhook data is not persisted and existing integration logging remains intact.

## 4. Verification

- [x] 4.1 Run focused Laravel tests through Sail.
- [x] 4.2 Run formatter/static checks for changed PHP files.
- [x] 4.3 Validate the OpenSpec change and inspect the final diff.
