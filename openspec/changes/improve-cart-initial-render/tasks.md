## 1. Preparation and baseline

- [x] 1.1 Confirm Issue #121 scope, `chore`/P2 classification, `dev` target, and isolated branch.
- [ ] 1.2 Capture the current cart initial-response status, transfer size, and time-to-first-byte before the deferred-prop change.

## 2. Deferred cart implementation

- [x] 2.1 Defer only authenticated-cart recommendations through the existing `CartRecommendationService` and preserve the eager cart resource and guest behavior.
- [x] 2.2 Wrap only the recommendation component with the native Inertia `Deferred` component and add a localized accessible fallback.

## 3. Regression coverage and verification

- [ ] 3.1 Add backend coverage for the initial deferred prop metadata, successful deferred resolution, recommendation exclusion, and guest/empty behavior.
- [x] 3.2 Add frontend coverage for the loading fallback, deferred prop resolution, unchanged cart interactions, and localized fallback copy.
- [ ] 3.3 Run focused frontend tests, canonical Sail backend tests, formatter/static checks, production build, and strict OpenSpec validation.
- [ ] 3.4 Verify `/cart` in the existing browser at `http://localhost:81` and compare after measurements with the baseline.

## 4. Delivery

- [x] 4.1 Review the final diff for scope, update Issue #121 status, commit only authorized files, push the branch, and open a PR targeting `dev` with issue/OpenSpec traceability and validation evidence.
