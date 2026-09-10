## 1. Preparation and baseline

- [x] 1.1 Confirm issue #118 scope, `chore`/P2 classification, `dev` target, and isolated branch.
- [x] 1.2 Capture a baseline homepage initial-response status, transfer size, and time-to-first-byte before the deferred-prop change.

## 2. Deferred homepage implementation

- [x] 2.1 Move product, category, slider, and flash-sale resolution into conditional deferred callbacks with one group per independent dataset while preserving existing query limits, resources, caches, pricing, and statistics.
- [x] 2.2 Annotate data-dependent entries in the homepage section registry and render localized accessible fallbacks through the native deferred component without changing section order or static sections.
- [x] 2.3 Keep unresolved optional data pending for its fallback, remove an explicitly empty flash sale after resolution, and preserve filtered homepage and template-preview behavior.

## 3. Regression coverage and verification

- [x] 3.1 Add backend coverage for the initial deferred-prop groups, successful deferred group resolution, and skipping unused dataset queries.
- [x] 3.2 Add frontend coverage for loading fallbacks, deferred prop resolution, failure-safe shell composition, and unchanged configured/legacy section order.
- [ ] 3.3 Run focused frontend tests, canonical Sail backend tests, formatter/static checks, production build, and strict OpenSpec validation.
- [x] 3.4 Verify the public homepage in the existing browser at `http://localhost:81`, including shell-first hydration, active section content, and responsive usability; capture after measurements and compare with the baseline.
- [ ] 3.5 Verify template-preview behavior in the browser with an authenticated admin session; this remains pending because the available browser session is unauthenticated.

## 4. Delivery

- [x] 4.1 Review the final diff for scope, update issue status, commit only authorized files, push the branch, and open a PR targeting `dev` with issue/OpenSpec traceability and validation evidence.
