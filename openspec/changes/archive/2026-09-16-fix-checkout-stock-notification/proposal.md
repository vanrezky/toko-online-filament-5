## Why

When two customers submit checkout for the last unit of the same product, the customer whose request loses the race receives Laravel's raw cart-not-found error instead of a useful stock-unavailable message. This makes a normal concurrent stock conflict look like an application failure and leaves the checkout response unusable for the customer.

## What Changes

- Convert a stale checkout caused by another successful checkout into a controlled stock-unavailable result.
- Return a localized, user-visible checkout error without exposing the `ModelNotFoundException` message.
- Preserve the successful checkout, stock consistency, and the invariant that the rejected request creates no transaction.
- Add regression coverage for the stale-cart/concurrent-last-unit scenario.

## Capabilities

### New Capabilities

- `checkout-stock-conflict-handling`: Controlled response and user notification when a checkout loses a concurrent last-unit stock race.

### Modified Capabilities

## Impact

- Backend checkout cart lookup and exception/validation response handling.
- Frontend checkout error display if the controlled response needs a different error shape.
- Checkout feature tests and localized messages.
- No database schema, dependency, payment provider, or public route changes.
