## Context

The checkout service performs an initial active-cart lookup and later locks the active cart inside the order transaction. A competing successful checkout can remove the cart items and mark the cart complete between those two reads. The later `firstOrFail()` then escapes the checkout error mapping as a `ModelNotFoundException`, producing a 404 response.

The existing inventory service already validates and reserves normal stock atomically, and the frontend already displays top-level checkout errors for non-validation responses. The change should reuse those boundaries and avoid changing inventory reservation, payment, or cart lifecycle semantics.

## Goals / Non-Goals

**Goals:**

- Map the stale-cart condition on the order-submission path to a controlled stock-unavailable checkout error.
- Preserve the existing successful checkout and rollback behavior.
- Provide a localized message through the response shape the checkout page already displays.
- Add one focused backend regression test for the stale-cart request.

**Non-Goals:**

- Do not introduce cart reservations, retries, schema changes, or new dependencies.
- Do not change shipping, voucher, payment, cancellation, or flash-sale behavior.
- Do not alter cart lookup behavior for unrelated checkout-page or shipping-cost requests.

## Decisions

1. **Handle the error at the checkout service boundary.** Catch the missing active cart only around the order-submission cart reads and convert it to a dedicated `CheckoutException`. This keeps the repository's query semantics intact and prevents a broad global exception handler from misclassifying unrelated model-not-found errors.

2. **Use the existing top-level checkout error path and toast system.** Add a dedicated error key and map it to a conflict response containing the localized stock message. The checkout page already imports `vue-sonner`; use its existing `toast.error()` path for top-level errors, so no new notification component or dependency is needed.

3. **Test the observable sequential equivalent of the race.** Complete the first checkout, then submit the second customer's stale request with the same checkout payload. This deterministically exercises the state transition that causes the production race while asserting no second transaction is created.

## Risks / Trade-offs

- [A stale cart can also result from another cart lifecycle action] -> The dedicated mapping is limited to the order-submission active-cart reads and returns a safe stock-unavailable message, while leaving other endpoints unchanged.
- [A 409 response is outside Laravel's default validation response] -> The response is intentionally a resource conflict and is consumed by the existing frontend fallback alert.

## Migration Plan

No database or deployment migration is required. Deploy the backend and frontend code together; rollback is a code-only revert.

## Open Questions

None.
