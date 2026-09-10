## 1. Planning and baseline

- [x] 1.1 Link Issue #120, confirm `chore`/P2 classification, `dev` target, and isolated branch.
- [x] 1.2 Capture before/after product-detail initial-response status, transfer size, and time-to-first-byte: both returned HTTP 200; payload 35,645 B -> 32,272 B; median TTFB 0.592 s -> 0.099 s.

## 2. Deferred product detail

- [x] 2.1 Move only related-product catalog resolution into the `relatedProducts` Inertia deferred callback, preserving all existing query, cache, pricing, reseller, and statistics behavior.
- [x] 2.2 Wrap the existing related-product section in native `<Deferred>` with an accessible localized loading fallback while preserving resolved links and empty behavior.

## 3. Verification

- [ ] 3.1 Add backend coverage for the initial deferred group and successful deferred resolution; execution is blocked by the existing test-environment `GeneralSettings` fixture gap.
- [x] 3.2 Add frontend coverage for the fallback, primary-content usability, and resolved related-product rendering.
- [ ] 3.3 Run focused tests, canonical Sail tests, formatter/static checks, production build, and strict OpenSpec validation; Sail `ProductDetailTest` is blocked by missing `GeneralSettings` rows in the refreshed test database, while frontend tests, Pint, build, and OpenSpec validation pass.
- [x] 3.4 Verify the product detail page in the existing browser at `http://localhost:81` and record performance evidence.

## 4. Delivery

- [ ] 4.1 Review scoped diff, update Issue #120 status, commit authorized files, push branch, and open the PR targeting `dev`.
