## Context

Issue #30 enables the existing `midtrans/midtrans-php` Snap adapter. The current checkout supports three internal `payment_type` values (`full`, `installment`, and `balance`), while the frontend has a dormant `payment_method` value and Snap bootstrap. `MidtransGateway` can create a token and verify a signature, but the checkout transaction path does not invoke it, and webhook processing does not bind its route parameter to the active gateway, compare values safely, verify amount or fraud state, or guard against stale state transitions.

Midtrans Snap requires a merchant-generated, unique order ID and an integer gross amount that exactly equals its item-detail subtotal. Its browser callbacks are untrusted; a server notification or a direct Midtrans status lookup is authoritative. Midtrans can resend notifications and notifications can arrive out of order.

## Goals / Non-Goals

**Goals:**

- Add a dedicated external `midtrans` checkout payment choice without changing existing internal payment semantics.
- Generate one trusted Snap payment request from a fully persisted and reconciled transaction, never disclose the Server Key, and provide safe retry/resume behavior.
- Treat a signature-verified, amount-matched Midtrans notification as the authoritative status signal and apply it idempotently.
- Preserve stock, voucher, cart, billing, credit, balance, and transaction-cancellation invariants.

**Non-Goals:**

- Build direct Core API, recurring, card-token, refund, split-payment, or any non-Midtrans gateway integration.
- Store payment-channel credentials or change the transaction schema unless implementation discovery establishes an essential persistence gap.
- Mark payment successful from browser callback data or replace the existing internal credit and balance flows.

## Decisions

### Separate internal funding type from external gateway selection

Checkout will continue to validate and create internal `full`, `installment`, and `balance` transactions exactly as today. It will add an explicit external Midtrans choice that creates a transaction with pending billing, then requests a Snap token after the database transaction commits.

This avoids treating an external payment as a credit-limit draw and keeps the selected gateway auditable in `payment_method`. The frontend will submit an explicit payment-method value, and backend validation will only allow Midtrans when it is both active and configured. The alternative—overloading `payment_type=full`—would conflate internal credit billing with cash/gateway payment and break Issue #25's contract.

### Request Snap after committing the local order

The local transaction, its product snapshot, vouchers, shipping, cart mutation, and reservation are committed before the backend calls Midtrans. The response returns Snap metadata only after a successful request. A Midtrans request failure is reported as a payment-initiation failure while preserving an unpaid local order that can be retried safely.

This prevents a remote HTTP call from holding database locks or being rolled back after Midtrans creates a payment. The alternative—calling Midtrans inside `DB::transaction()`—risks long locks, duplicate remote orders on a database rollback, and checkout contention.

### Enforce canonical Snap payload arithmetic and immutable order identity

`MidtransGateway` will build item details solely from transaction snapshots, explicit voucher records, shipping, and fees, normalize all currency values to integer IDR, and assert that its item total equals the transaction total before requesting Snap. The UUID transaction order ID is used once for the initial Snap request; subsequent customer payment attempts retrieve or regenerate only under an explicitly safe, pending/expired-state policy defined by the provider response.

Item IDs are made unique and constrained to Midtrans length rules. Existing discount splitting may be retained only if its output is verified exact and non-negative. The alternative of trusting frontend totals or cart rows would permit price drift and violate Midtrans's item-total requirement.

### Round whole-IDR financial values at checkout before persistence

The final checkout recalculation will round each monetary component using half-up whole-IDR rounding before it creates the transaction: resolved product unit price, original price/discount representation, shipping charge, and voucher discount. Transaction product line subtotals, shipping details, vouchers, transaction totals, credit checks, installment calculations, order resources, and the Snap payload will consequently use the same integer values. The checkout page mirrors the same calculation only as a preview; the server-side persisted transaction remains authoritative.

This means cart browsing may retain source prices with decimals, but the final checkout page clearly presents the payable whole-IDR amounts that will be saved and charged. Rounding only the Snap payload is rejected because it leaves local orders and audit records inconsistent with the charged amount.

### Webhook authenticates, reconciles, then applies monotonic state transitions

The route parameter must equal the currently active Midtrans gateway. The gateway will use `hash_equals` to check the SHA-512 signature, validate `order_id`, status code, amount, and `fraud_status` where supplied, and return a normalized result. The controller locks the local transaction and only moves it forward:

- verified `capture`/`settlement` with successful code and acceptable fraud result -> paid;
- verified `pending` -> pending only when not already paid or cancelled;
- verified denial/rejection -> failed only when not paid or cancelled;
- verified expiry/cancellation -> delegate once to the existing cancellation service only when the local transaction is still cancellable and unpaid.

Duplicate or stale notifications become no-ops with a successful acknowledgement. A mismatched amount, invalid signature, inactive gateway, or unknown order is rejected without mutation. This uses the existing transaction/cancellation boundaries instead of adding a duplicate payment ledger. The alternative—accepting Snap browser callbacks or assigning status unconditionally—would enable forged or state-regressing payments.

### Frontend uses Snap only as a payment UI

The checkout and pay-again flows lazily load the correct Snap script for the configured mode and call `snap.pay` with backend-issued token and client key. The order detail exposes this action only for the owned, packed, pending full Midtrans order. Its label makes clear that Snap can resume payment or choose another available Midtrans channel; it does not mutate the local transaction payment type or billing contract. All callbacks reload or redirect to the owned order detail; they do not set payment state. The visible choice appears only for an active, configured Midtrans adapter; other future gateway tiles remain disabled.

### Verify webhook delivery configuration operationally

The project documentation will state the exact Notification URL (`/webhooks/payment/midtrans`), public HTTPS/standard-port requirement, dashboard configuration, sandbox credentials, and test instructions. The endpoint must return JSON rapidly and must not log sensitive credentials or full untrusted payload unnecessarily.

### Use the existing Settings cluster and Shield page permission

`ManagePaymentGateway` will be registered under `SettingsCluster`, with ordinary navigation enabled. It will use its generated Shield page permission for role-based access and explicitly allow authenticated users whose `is_super_user` flag is true. The existing hard-coded denial and Shield exclusion will be removed. This keeps roles least-privileged while preserving the application's database-backed super-user convention; merely placing the page in the cluster must not grant access.

## Risks / Trade-offs

- [Midtrans creates a token but the response fails before the browser receives it] -> retain the unpaid local order and provide owned-customer resume payment; never create a second local order.
- [Duplicate, delayed, or out-of-order notifications] -> lock the transaction, use monotonic transitions, and acknowledge idempotent no-ops.
- [Gateway notification is forged or amount is altered] -> require constant-time signature verification and local amount match before mutation; never trust browser callback data.
- [Provider status is temporarily unavailable] -> leave the local order pending, surface a retryable error, and use a status-query reconciliation path for manual/operational recovery.
- [Existing incomplete adapter has hidden payload assumptions] -> add focused payload and webhook tests before enabling the checkout tile, and maintain unchanged internal payment tests.
- [Production Notification URL is not public] -> document configuration and validate sandbox delivery using a publicly reachable HTTPS endpoint before production activation.

## Migration Plan

1. Deploy code with Midtrans disabled by default and run all tests/build checks.
2. Configure sandbox Server Key, Client Key, mode, active gateway, enabled channels, and the public Notification URL in the Midtrans dashboard.
3. Exercise success, pending VA, deny/error, expiry, duplicate-notification, and retry scenarios in sandbox; compare local order state with Midtrans dashboard/status API.
4. Enable production credentials and URL only after sandbox sign-off.
5. Roll back by setting the active gateway away from Midtrans; existing pending external orders remain visible and no internal payment behavior changes. If code rollback is necessary, keep the webhook endpoint reachable until pending Midtrans orders settle or expire.

## Open Questions

- Which Midtrans channels are contractually enabled for this merchant, and should the initial UI expose all dashboard-enabled channels or a curated subset?
- Does product policy require a customer-facing Midtrans payment label/virtual-account details on the order page beyond the Snap resume button?
- What public sandbox URL/tunnel will be used for end-to-end notification testing in the deployment environment?
