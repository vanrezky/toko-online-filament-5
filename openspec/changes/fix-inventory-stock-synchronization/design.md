## Context

Issue #116 spans cart validation, product variant administration, checkout, payment expiry, and customer cancellation. Current flash-sale inventory has its own reservation ledger, but normal product and variant stock is not changed during checkout. `transcation_products` also lacks a persisted variant foreign key even though the model already declares a variant relation.

## Goals / Non-Goals

**Goals:** Make variant stock the effective source for variant cart lines, keep the product aggregate synchronized, reserve normal inventory atomically at order creation, preserve enough order data to release it, and make every cancellation entry point idempotent.

**Non-Goals:** Reserving inventory while items are merely in a cart, changing flash-sale quota semantics, changing payment-provider contracts, or adding a new public route.

## Decisions

1. **Centralize normal inventory operations.** Add a small `ProductInventoryService` with methods for aggregate synchronization, checkout reservation, and cancellation release. It will use deterministic row locking and `ValidationException` for insufficient stock so controller and service behavior share one rule.

2. **Use variant rows as the aggregate source.** A `ProductVariant` saved or deleted event will recalculate its parent product stock whenever variants exist. Products with no variants continue to use their direct `products.stock` value. Checkout locks the product and all variants for each affected product before validating and updating, which prevents a concurrent variant edit or checkout from producing a lost aggregate update.

3. **Persist the order's variant identity.** Add nullable `product_variant_id` to the existing misspelled `transcation_products` table with `nullOnDelete()` for historical compatibility. Add it to `TransactionProduct::$fillable` and write it during checkout. The stored product/variant snapshot remains useful for display if a referenced variant is later removed.

4. **Reserve normal stock in the existing checkout transaction.** After the final cart pricing is resolved and before cart rows are deleted, the inventory service will lock and validate all selected lines, then decrement normal stock. If any line fails, the surrounding transaction rolls back the transaction, flash-sale reservation, cart updates, and inventory changes together.

5. **Release by locked transaction state.** `TransactionCancellationService` will lock the transaction row before checking its status. Only the first caller can move it to `cancelled` and release normal inventory; later callers return after observing the cancelled state. Existing flash-sale and balance release mechanisms remain in this same idempotent path.

6. **Expose effective stock to the cart.** `CartResource` will add an `available_stock` line field derived from the selected variant or product. `Cart/Index.vue` will use that field for the quantity stepper and stock status, while `ProductResource` and the existing product-detail computed state continue to use the selected variant stock.

7. **Keep error handling localized.** Add matching Indonesian and English stock validation text where a new user-facing message is needed, and retain the current generic cart-update fallback for Inertia errors. The backend response remains a standard Laravel validation response.

## Transaction / Locking Order

For checkout and release, the service will lock rows in this order:

```text
transaction (cancellation only)
    -> products ordered by id
        -> all variants ordered by id
```

Checkout will group requested quantities by product and variant, validate from the locked rows, decrement variant rows first, then set the locked product aggregate to the remaining sum. Non-variant products only decrement their product row. All operations run inside the caller's database transaction.

## Risks / Trade-offs

- Existing orders have no variant identity; the nullable migration keeps them releasable at the product level without inventing a variant.
- A variant deleted after an order is created cannot receive a restored variant row; product-level inventory restoration remains possible and the snapshot preserves order display data.
- Model-event synchronization may update product stock during bulk admin operations; this is intentional so every variant mutation follows the same invariant.
- Checkout locking all variants of an affected product is more work than locking only selected variants, but it prevents aggregate stock races with edits to another variant.

## Rollout / Migration

Run the nullable transaction-product migration before deploying code. Existing products with variants will be normalized when a variant is next saved or deleted; a one-time data command is not introduced in this scoped change. Existing flash-sale reservation rows remain unchanged.
