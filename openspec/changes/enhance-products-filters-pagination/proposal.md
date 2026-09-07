## Issue

GitHub Issue: #99

## Why

The Products page currently supports only basic search, category, price, and sorting controls, and loads additional items through a “load more” action. The approved Products mockup requires a complete, responsive catalog discovery experience with variant-aware filters and predictable pagination so customers can narrow products efficiently on desktop and mobile.

## What Changes

- Add a responsive Products filter experience with a persistent desktop sidebar and mobile bottom-sheet.
- Support common catalog filters for category, price, minimum rating, color, size, gender, discount, new products, and flash sale.
- Use `variant_*` query parameters for the supported variant attributes `variant_color`, `variant_size`, and `variant_gender`.
- Accept single and multi-value variant filters and resolve them through product variant attribute relations without duplicate products.
- Replace “load more” with numbered pagination and a configurable products-per-page control.
- Preserve filters, sorting, search, and page-size parameters in URLs and pagination links.
- Add active-filter chips, reset actions, empty state behavior, and Indonesian/English UI copy consistent with the mockup.

## Capabilities

### New Capabilities

- `storefront-product-catalog-filtering`: Filter, sort, and paginate the storefront product catalog using common product and variant attributes.

### Modified Capabilities

None.

## Impact

- `app/Http/Controllers/Frontend/ProductController.php` query parameters, supported variant-aware filtering, and pagination.
- `resources/js/frontend/pages/Products/Index.vue` responsive filter UI, active chips, sorting, page size, and pagination.
- Frontend Indonesian and English locale files.
- Backend and frontend regression tests for catalog filtering and pagination.
- No database schema changes or checkout/pricing changes.
