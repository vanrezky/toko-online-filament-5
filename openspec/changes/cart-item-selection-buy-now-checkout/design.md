## Context

Issue #21 changes the checkout contract from implicitly consuming an entire active cart to optionally consuming an explicit subset. The current checkout controller independently reads all active items for page rendering, shipping costs, voucher validation, final flash-sale resolution, transaction products, and cart completion. Buy Now first creates or merges a cart item but loses that item's identity when redirecting to checkout.

## Goals / Non-Goals

**Goals:**

- Use one validated active-cart subset consistently through every checkout read and write.
- Preserve all-active-cart behavior for existing cart, slide-panel, and direct checkout entry points that omit selection.
- Retain unpurchased items after a successful partial checkout without weakening ownership validation.

**Non-Goals:**

- Persist a reusable cart-selection session, change cart quantities, or change voucher policy.
- Change payment-gateway contracts, shipping-provider behavior, cart schema, or flash-sale rules.

## Decisions

### Carry `cart_item_ids` in checkout URLs and submission payloads

Buy Now and selected-cart checkout will navigate to `/checkout?cart_item_ids[]=...`; the checkout page keeps those IDs and sends them when shipping costs and order creation are requested. Query parameters make refreshes and shareable navigation retain selection without a new database column. The alternative of server-side session state would add invisible state and make competing checkout tabs ambiguous.

### Resolve selection once per controller entry point from the authenticated active cart

`CheckoutController` will accept an omitted `cart_item_ids` as all active items. When present, it will require a non-empty, distinct list whose IDs belong to the authenticated customer's active cart; absent, duplicate, stale, or foreign IDs receive validation failure. The controller will attach only that subset to the cart passed to downstream services. Client-provided product data is never trusted.

### Keep an active cart until it contains no remaining items

After transaction creation succeeds, selected rows are deleted from the active cart. The cart moves to `checked_out` only when no active items remain. This preserves unselected cart contents. The alternative of marking the entire cart checked out would make partial checkout destructive.

### Revalidate the same subset while flash-sale rows are locked

The transaction closure reloads and resolves only the selected IDs before pricing, voucher, reservation, transaction-product, and payment calculations. This retains checkout as final pricing authority and prevents a stale page request from expanding its purchase set.

## Risks / Trade-offs

- [Selection changes between page load and order submission] → validate selected IDs again at each endpoint and inside the final transaction.
- [Shipping methods include warehouses outside selection] → derive required warehouse groups from selected items and reject malformed shipping payloads where validation is necessary.
- [Voucher cookies are shared across checkout tabs] → evaluate the voucher only against the resolved subset; no new persistent selection state is introduced.
- [Cart is emptied while another checkout request is pending] → final transaction locks and validates only the requested active items before writing a transaction.

## Migration Plan

No schema migration is required. Deploy compatible optional `cart_item_ids` handling first with all-cart fallback, then expose Buy Now and selected cart links. Rollback restores all-cart behavior; any remaining active items remain intact and completed transactions remain immutable.

## Open Questions

None.
