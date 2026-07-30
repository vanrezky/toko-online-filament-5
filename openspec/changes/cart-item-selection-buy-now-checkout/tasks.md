## 1. Checkout selection contract

- [x] 1.1 Add optional ownership-validated active-cart item selection to checkout page, shipping-cost, and order-creation endpoints, retaining all-cart fallback.
- [x] 1.2 Scope vouchers, shipping, payment checks, final flash-sale resolution, reservations, and transaction products to the resolved selection.
- [x] 1.3 Remove only purchased cart items and mark a cart checked out only after its final active item is purchased.

## 2. Storefront entry points

- [x] 2.1 Return the resolved cart item identity from JSON cart additions and make Buy Now navigate with that explicit selection.
- [x] 2.2 Add selected-item checkout from the cart page, retain all-cart checkout controls, and localize new visible strings.
- [x] 2.3 Preserve selected item IDs in checkout shipping-cost and order submissions.

## 3. Verification

- [x] 3.1 Add feature coverage for subset ownership validation, all-cart fallback, and partial-cart completion.
- [x] 3.2 Run focused backend tests, frontend build, strict OpenSpec validation, and diff checks.
