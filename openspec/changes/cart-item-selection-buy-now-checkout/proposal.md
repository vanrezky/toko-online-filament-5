## Why

Issue #21: the storefront Buy Now action adds an item to the active cart and opens checkout, but checkout always processes every active cart item. Customers therefore cannot purchase the chosen product independently and may unintentionally include unrelated cart items.

## What Changes

- Carry explicitly selected active-cart item IDs from Buy Now and selected cart checkout into checkout.
- Scope checkout rendering, shipping, voucher validation, pricing, payment eligibility, flash-sale reservation, and transaction creation to one ownership-validated item subset.
- Preserve the existing all-active-cart checkout behavior when no item selection is supplied.
- Remove only successfully purchased selected items; leave unselected active items in the cart.

## Capabilities

### New Capabilities

- `checkout-item-selection`: Supports checkout of an explicit active-cart item subset while retaining all-cart fallback behavior.

### Modified Capabilities

- None.

## Impact

- Backend: cart resource, checkout routes/controller, voucher and flash-sale checkout inputs, cart completion behavior.
- Frontend: product Buy Now, cart selection/checkout links, cart slide panel, and checkout request preservation.
- Tests: checkout ownership, selection scope, all-cart fallback, and partial-cart completion coverage.
