## 1. Timezone and settings foundation

- [x] 1.1 Add customer and transaction timezone persistence plus the General Settings migration for one bounded transaction-limit duration.
- [x] 1.2 Add the transaction-limit field to Settings Commerce with Indonesian translation and preserve existing deadlines when the value changes.
- [x] 1.3 Validate and persist the checkout browser IANA timezone, snapshot it on every new transaction, and calculate its UTC deadline from the configured duration.

## 2. Expiry reconciliation

- [x] 2.1 Restrict expiry discovery to eligible pending transactions and run its scheduler every minute on one server.
- [x] 2.2 Implement an idempotent queued expiry job that locks/rechecks the transaction, reconciles external provider status, and uses the existing cancellation service.
- [x] 2.3 Coordinate expiry and webhook settlement transitions so paid transactions cannot be cancelled by delayed or duplicate expiry work.
- [x] 2.4 Keep retry, logging, notification, and queue behavior safe when Redis or external-provider status lookup is temporarily unavailable.

## 3. Customer order experience

- [x] 3.1 Expose the UTC deadline and timezone snapshot through the order resource for eligible unpaid orders.
- [x] 3.2 Add localized deadline and informational countdown UI to order detail, disabling the Midtrans resume action locally at deadline and refreshing server state without browser-side mutation.

## 4. Verification and operational readiness

- [x] 3.3 Send Midtrans Snap expiry and page-expiry values from the persisted transaction deadline, and prevent token creation with fewer than five minutes remaining.

- [ ] 4.1 Add migration/settings, checkout timezone, expiry-job, duplicate-job, internal-credit exclusion, and webhook-race feature tests.
- [ ] 4.2 Add or update frontend tests for timezone payload and deadline/countdown rendering.
- [x] 4.3 Run focused PHP/frontend tests, build, strict OpenSpec validation, and scoped diff checks.
- [ ] 4.4 Document Redis queue/scheduler and Midtrans sandbox testing requirements for operational rollout.
