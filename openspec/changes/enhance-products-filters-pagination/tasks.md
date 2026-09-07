## 1. Backend filter contract

- [x] 1.1 Add normalized allowlisted catalog filter input for search, category, price bounds, rating, promotion, sort, page size, and page.
- [x] 1.2 Add `variant_*` normalization supporting scalar, repeated-array, and comma-separated values.
- [x] 1.3 Apply variant attribute option constraints for color, size, and gender using product variant relationships; ignore brand and unsupported dynamic variant keys.
- [x] 1.4 Add common catalog constraints for minimum rating, discount, new products, and active flash sale without changing pricing resolution.
- [x] 1.5 Preserve the supported filter set in paginator query strings without returning dynamic variant-option metadata.

## 2. Products page experience

- [x] 2.1 Replace the local “load more” accumulation model with paginator-driven product data and numbered pagination.
- [x] 2.2 Implement one reactive filter state shared by desktop sidebar and mobile bottom-sheet layouts.
- [x] 2.3 Add category, price range, minimum rating, common hardcoded color/size/gender, and promotion controls matching the mockup.
- [x] 2.4 Preserve the existing topbar search query, add sort/page-size controls, active filter chips, per-chip removal, reset-all action, result count, and empty state behavior without duplicating search on the Products page.
- [x] 2.5 Add responsive styling, accessible labels/focus states, and loading behavior using existing design tokens and UI patterns.
- [x] 2.6 Add Indonesian and English locale entries for every new visible label, filter option label, pagination action, and empty/loading state.

## 3. Regression coverage

- [x] 3.1 Add backend tests for the supported color, size, and gender variant filter contract.
- [x] 3.2 Add backend tests for combined filters, duplicate prevention, invalid values, promotion/rating constraints, and pagination query preservation.
- [x] 3.3 Add frontend tests for shared filter state, active-chip removal/reset, mobile filter application, and numbered pagination.

## 4. Verification traceability

- [x] 4.1 Run focused backend tests through Laravel Sail and diagnose any Sail/container issue without falling back to host PHP.
- [x] 4.2 Run focused frontend tests and `npm run build`.
- [x] 4.3 Validate the OpenSpec change with `openspec validate enhance-products-filters-pagination --type change --strict`.
- [x] 4.4 Perform browser verification at `http://localhost:81` for desktop and mobile layouts and record the result against Issue #99.
