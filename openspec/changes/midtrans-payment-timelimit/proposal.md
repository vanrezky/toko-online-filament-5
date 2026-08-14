## Why

Orders are currently assigned a fixed one-day time limit without a customer timezone, customer-facing deadline, or configurable business policy.

## What Changes

- Add a configurable transaction time-limit duration to Settings Commerce for every payment method and gateway.
- Capture and validate an IANA timezone for the customer, snapshot it on each Midtrans transaction, and calculate the persisted deadline as an absolute UTC instant.
- Display the deadline and a non-authoritative countdown on the customer order-detail page.
- Use a queued, idempotent cancellation path for every pending transaction, coordinated with external gateway settlement processing.
- Run the expiry scan every minute and retain the database deadline as the source of truth; Redis may be used as the configured queue broker.

## Capabilities

### New Capabilities

- `midtrans-payment-timelimit`: Configure, persist, display, and enforce timezone-safe expiry for pending Midtrans payments.
- `midtrans-expiry-reconciliation`: Reliably enqueue and apply automatic Midtrans expiry cancellation without regressing a concurrent paid transaction.

### Modified Capabilities

- `checkout-payment-method-grouping`: Capture the customer's timezone when a configured Midtrans payment is selected at checkout.

## Impact

- Customer and transaction schema, customer timezone capture, `GeneralSettings`, Settings Commerce, checkout creation, `OrderResource`, and order-detail Vue UI.
- `orders:check-expiry`, scheduler cadence, new queued job, cancellation service usage, and Midtrans webhook locking/reconciliation.
- PHP migration/feature/job tests, frontend timezone/countdown tests, translations, and Redis-compatible queue operations.
