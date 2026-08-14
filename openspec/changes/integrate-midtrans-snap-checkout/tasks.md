## 1. Gateway contract and configuration

- [ ] 1.1 Audit transaction totals, billing enums, cancellation semantics, gateway settings, and existing Midtrans SDK usage against the approved design.
- [ ] 1.2 Implement authoritative whole-IDR checkout rounding before transaction persistence and reuse it for credit, installment, order, and Snap totals.
- [ ] 1.3 Publish the credential form in the Settings cluster and protect it with its Shield page permission plus the `is_super_user` override.
- [ ] 1.4 Harden the Midtrans gateway configuration path and add an explicit active-and-configured availability contract without exposing Server Key data.
- [ ] 1.5 Build and validate canonical Snap payloads from persisted transaction snapshots with exact IDR integer totals, unique item IDs, configured channels, and browser-safe response metadata.
- [x] 1.6 Expose copyable Midtrans notification and redirect URLs in protected gateway settings, with public HTTPS deployment guidance.

## 2. Checkout and payment initiation

- [ ] 2.1 Add explicit backend validation and transaction creation for the Midtrans external payment selection while preserving internal full, installment, and balance contracts.
- [ ] 2.2 Request Snap only after local checkout persistence commits; return retryable payment-initiation errors without rolling back a valid unpaid order.
- [ ] 2.3 Update the owned-order pay endpoint to resume an eligible pending Midtrans payment without duplicate checkout side effects.
- [x] 2.4 Update checkout and order-detail frontend flows to show configured Midtrans, load the correct Snap script lazily, and redirect after callbacks without changing payment status locally.
- [x] 2.5 Localize all new customer-visible payment text in Indonesian and English using the established frontend catalog.
- [ ] 2.6 Make the checkout preview and order detail display the persisted whole-IDR values used for payment and audit.

## 3. Secure webhook reconciliation

- [ ] 3.1 Restrict the payment webhook to its selected active gateway and remove sensitive or excessive notification logging.
- [ ] 3.2 Verify Midtrans notification structure and signature with `hash_equals`, validate local order ownership/provider/amount, and normalize successful, pending, failed, expired, and cancelled outcomes.
- [ ] 3.3 Apply webhook outcomes under transaction locking with idempotent, monotonic state transitions and the existing cancellation service for eligible expiry/cancellation.
- [ ] 3.4 Document sandbox/production credentials, the public Midtrans Notification URL, dashboard configuration, and operational status reconciliation.

## 4. Automated verification

- [ ] 4.1 Add Midtrans gateway tests for configuration, payload arithmetic, channels, safe metadata, and token/initiation failures.
- [ ] 4.2 Add checkout and resume-payment feature tests proving Midtrans initiation is isolated from full, installment, and balance internal flows.
- [ ] 4.3 Add webhook feature tests for valid and invalid signatures, amount mismatch, fraud/status rules, duplicates, stale notifications, and cancellation idempotency.
- [ ] 4.4 Add or update frontend tests for Midtrans availability and Snap browser integration behavior.
- [ ] 4.5 Run focused PHP/frontend tests, formatter/static checks, frontend build, strict OpenSpec validation, and scoped diff checks; record sandbox notification validation separately when credentials and public URL are available.
