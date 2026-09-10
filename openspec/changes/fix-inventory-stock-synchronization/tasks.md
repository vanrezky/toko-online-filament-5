## 1. Preparation and data model

- [x] 1.1 Confirm Issue #116 and Full OpenSpec scope, inspect schema/checkout/cancellation paths, and keep the implementation isolated from unrelated worktree changes.
- [x] 1.2 Add a nullable `product_variant_id` foreign key to `transcation_products`, update the transaction product model, and include the variant in order creation/snapshot data.

## 2. Inventory behavior

- [x] 2.1 Implement `ProductInventoryService` for effective stock lookup, variant-to-product aggregate synchronization, locked checkout reservation, and idempotent normal-stock release.
- [x] 2.2 Hook product-variant create/edit/delete lifecycle events to aggregate synchronization, including Filament relation-manager mutations and bulk deletion.
- [x] 2.3 Validate cart add/update quantities with the selected variant/product stock and existing same-line quantity; reject mismatched product/variant references safely.
- [x] 2.4 Integrate locked normal-stock reservation into checkout without changing selected-cart-item, price, voucher, or flash-sale behavior.
- [x] 2.5 Integrate normal-stock release into the shared cancellation service and verify customer cancellation, expiry, and payment cancellation remain idempotent.

## 3. Storefront and localization

- [x] 3.1 Expose effective per-line stock in `CartResource` and use it for cart quantity limits/status; preserve the selected variant stock on product detail.
- [x] 3.2 Add or update Indonesian and English messages for stock validation and cart update feedback.

## 4. Automated verification

- [x] 4.1 Add backend regression tests for cart add/update limits, same-product variant isolation, variant ownership, and product aggregate synchronization after create/edit/delete.
- [x] 4.2 Add backend checkout tests for product/variant decrement, transaction variant persistence, insufficient stale-stock rollback, and non-variant behavior.
- [x] 4.3 Add backend cancellation/expiry/webhook tests proving normal stock restoration exactly once and no restoration for successful orders; preserve existing flash-sale and balance tests.
- [ ] 4.4 Update focused frontend tests for effective cart stock and run frontend checks; run backend tests and formatting through Sail after inspecting `docker compose ps`.

## 5. Final verification and ship

- [x] 5.1 Validate the OpenSpec change strictly and map each Issue #116 acceptance criterion to code/tests/evidence.
- [x] 5.2 Review the diff for scope, run the relevant build/checkpoint validation, then commit and push an issue-linked branch targeting `dev`.
- [x] 5.3 Open a PR to `dev` linking Issue #116 and the OpenSpec change, with validation results and any environment limitations recorded.
