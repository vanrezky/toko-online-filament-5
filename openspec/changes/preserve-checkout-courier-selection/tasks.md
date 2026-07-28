## 1. Checkout shipping reconciliation

- [x] 1.1 Reconcile each latest warehouse response with the selected courier code, retaining it only when it remains available and refreshing its details.
- [x] 1.2 Select a valid fallback only for unavailable couriers, remove stale warehouse selections, and notify the customer once.
- [x] 1.3 Ignore stale shipping-cost responses so only the latest address request changes checkout state.

## 2. Regression coverage and validation

- [x] 2.1 Add frontend tests for retained selection, unavailable fallback, multi-warehouse reconciliation, and stale-response protection.
- [x] 2.2 Add Indonesian and English fallback-warning translations.
- [x] 2.3 Run targeted frontend tests, frontend build, and OpenSpec validation.
