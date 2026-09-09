## 1. Traceability and Preparation

- [x] 1.1 Confirm approval of the revised Issue draft, recheck duplicates and required labels, create the authorized GitHub Issue, and add its number/URL to proposal.md before implementation.
- [x] 1.2 Review the Issue against proposal, design, and spec; record scope approval and prepare one issue-linked branch while preserving unrelated worktree files.
- [x] 1.3 Inspect current cart props, frontend actions, wrapper, locale namespaces, and available image assets; identify each supplemental field/action requiring presentation data or local state.

## 2. Cart Presentation

- [x] 2.1 Implement cart-scoped desktop/tablet layout, heading, selection row, item information, prices/subtotals, quantity controls, and actions against dekstop.png and tablet.png.
- [x] 2.2 Implement shopping summary and real catalog recommendation presentation; omit the non-cart benefit/information strip plus voucher entry/discount placeholder, shipping/payment methods, and security block per feedback.
- [x] 2.3 Implement the revised mobile order (items, summary, recommendations), responsive item layout, and fixed checkout bar with safe-area/content clearance; ensure only one primary checkout action is visible and keyboard-accessible per viewport.
- [x] 2.4 Preserve real item selection, quantity update, delete, selected subtotal, checkout-ID navigation, and empty-cart behavior; keep newly unsupported actions local.
- [x] 2.5 Add matching Indonesian and English locale keys using existing formatting helpers; use shared storefront container, font, color tokens, and Button conventions from product detail/home.
- [x] 2.6 Add a cart recommendation service that derives dominant categories from distinct cart lines, uses shared managed ID caches, excludes cart products, interleaves category pools, applies a global fallback, hydrates fresh catalog relations/stats, and returns at most five products.
- [x] 2.7 Integrate real recommendations into the cart Inertia response and replace static recommendation samples without changing checkout payloads or transaction totals.

## 3. Verification

- [x] 3.1 Add or update focused frontend tests covering all/subset/no selection, quantity minimum/update, deletion, selected-ID checkout, empty cart, and isolation of recommendations from transaction payloads.
- [x] 3.1a Add backend tests for category weighting, exclusion, inactive/out-of-stock filtering, fallback, cache reuse/invalidation behavior, fresh hydration, and the Inertia recommendation contract.
- [x] 3.2 Run relevant frontend tests, scoped formatting checks, and npm run build; record results and fix regressions within scope.
- [x] 3.3 Inspect the existing environment with docker compose ps before browser/service-dependent verification; use http://localhost:81 without starting a replacement server. If backend validation becomes necessary, use Sail only.
- [x] 3.4 Compare browser screenshots with desktop, tablet, and mobile references; verify long names/variants, horizontal overflow, keyboard access, bar overlap, and both locales. Record viewport dimensions and remaining visual differences.
- [x] 3.5 Verify existing cart actions in the browser with suitable test data and inspect checkout navigation IDs; document which extra sections/actions are static or local-only.
- [x] 3.6 Verify the final diff has no database/schema, endpoint-contract, or backend transaction-rule changes; run strict OpenSpec validation and diff whitespace checks.
- [x] 3.7 Map each Issue acceptance criterion to specification scenarios and evidence as passed, partial, missing, or conflicting; leave unverified implementation tasks unchecked.
