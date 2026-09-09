# Verification

## Acceptance criteria

| Criterion                                                                                               | Status | Evidence                                                                                                                                                                                                                                                          |
| ------------------------------------------------------------------------------------------------------- | ------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Desktop, tablet, and mobile cart follow the approved reference composition                              | Passed | Browser QA at 1440px, 1024px, and 390px verified item/summary placement, mobile fixed checkout clearance, and recommendation layout.                                                                                                                              |
| Existing selection, quantity, deletion, subtotal, and selected-ID checkout behavior remains operational | Passed | `tests/Frontend/CartIndex.test.js` covers selection states, serialized quantity writes, rollback, deletion, clear-all, subtotal, and checkout IDs; live browser QA verified quantity persistence, deletion, and selected checkout IDs.                            |
| Variant is hidden when absent and rendered as read-only text when present                               | Passed | Focused frontend assertions and browser QA confirm no fallback variant and no dropdown control.                                                                                                                                                                   |
| Voucher, voucher discount, shipping/payment methods, and security blocks are absent                     | Passed | Focused frontend assertions and browser inspection; the concise checkout shipping notice remains.                                                                                                                                                                 |
| Recommendations use real, active, in-stock catalog products related to cart categories                  | Passed | `CartRecommendationTest` verifies category weighting, cart-product exclusion, inactive/out-of-stock filtering, five-item fallback, and the Inertia resource contract. Browser QA showed five distinct real-product detail links with no overlap against the cart. |
| Recommendation cache is bounded, shared, invalidated, and fresh at hydration                            | Passed | Service caches only 24 candidate IDs per category/global for 300 seconds in `product-catalog`; tests verify cache reuse, stock recheck, and invalidation after product save.                                                                                      |
| Recommendation query uses the intended product index                                                    | Passed | MySQL `EXPLAIN`: `type=range`, `key=products_related_lookup_index`, `Using index condition; Using where; Backward index scan`.                                                                                                                                    |
| Indonesian and English UI remain complete                                                               | Passed | Locale assertions cover both languages with no raw cart keys rendered.                                                                                                                                                                                            |
| No schema, endpoint-contract, or checkout transaction-rule change                                       | Passed | Diff inspection shows no migration/schema/route/checkout changes; recommendations are an additional Inertia prop only.                                                                                                                                            |

## Automated validation

- Laravel Sail full suite: 314 tests passed, 1261 assertions.
- Frontend full suite: 21 files passed, 69 tests passed.
- Production Vite build passed.
- Scoped Pint and Prettier passed.
- Strict OpenSpec validation passed.
- `git diff --check` passed.

## Limits

- Save-for-later remains local UI state because persistence is outside Issue #114.
- Frequently-bought-together and cross-session personalization remain outside this change; the implemented strategy uses current cart category affinity.
