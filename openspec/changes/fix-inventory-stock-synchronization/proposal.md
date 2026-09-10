## Why

Issue #116 identifies inconsistent inventory behavior across the storefront and order lifecycle: cart quantities can exceed the available product or variant stock, product stock can diverge from the sum of its variants, and checkout currently does not reserve normal inventory. This can permit overselling and makes cancellation/expiry unable to restore the exact inventory that was sold.

## Issue and Planning Status

- GitHub Issue: [#116](https://github.com/vanrezky/toko-online-filament3/issues/116).
- Classification: `bugfix`, `P2`, `COMPLEX` with Full OpenSpec because the change spans cart, checkout, admin variant management, payment expiry/cancellation, and transactional data integrity.
- Target branch: `dev`.

## What Changes

- Validate cart additions and quantity updates against the selected variant stock, or product stock for products without variants, while accounting for the existing quantity of the same product/variant in the customer's active cart.
- Expose the effective per-item stock to the cart and use it for quantity limits; keep different variants independent.
- Keep `products.stock` synchronized to the sum of all variant stocks whenever variants are created, edited, or deleted; products without variants retain their direct stock.
- Persist the selected variant on transaction product rows so inventory reserved at checkout can be released against the same variant.
- Reserve/decrement product and variant inventory atomically during checkout, with locked rows and stale-frontend revalidation; roll back the complete order when stock is insufficient.
- Restore normal inventory exactly once when an order is cancelled by the customer, payment webhook, or expiry job. Successful orders retain their decremented stock.
- Preserve existing flash-sale reservation behavior and the selected-cart-item checkout flow.

## Capabilities

### New Capabilities

- `inventory-stock-synchronization`: Cart, product-variant administration, checkout inventory reservation, and idempotent cancellation restoration.

### Modified Capabilities

None. No existing canonical capability spec currently defines normal inventory lifecycle behavior.

## Impact

- Backend: `CartController`, `CheckoutController`, `TransactionCancellationService`, payment/expiry cancellation paths, product and variant models, transaction product persistence, and a new inventory service/migration.
- Admin: product variant create/edit/delete hooks and product stock display synchronization.
- Frontend: cart effective-stock data/quantity limits, product detail variant stock presentation, and localized stock/update errors.
- Tests: cart validation, product/variant synchronization, checkout stock locking/rejection, variant persistence, and cancellation/expiry/webhook idempotency.
- Database: nullable `product_variant_id` on `transcation_products` with a foreign key compatible with historical orders.
- No new payment provider, cart reservation, or public route is introduced.
