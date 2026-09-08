## 1. Product detail data contract

- [x] 1.1 Update the detail product resource so every media item exposes its original URL for the main gallery and a separate thumbnail URL with original-URL fallback when the `thumb` conversion is unavailable.
- [x] 1.2 Add a bounded active same-category related-product selection to the detail request and expose it through the existing compact product resource without changing listing or cart payloads.
- [x] 1.3 Add or update backend regression coverage for original media URLs, thumbnail URLs, conversion fallback, and related-product exclusion.

## 2. Gallery and product interaction

- [x] 2.1 Refactor detail gallery state to pair original and thumbnail media, keep navigation thumbnails lazy, and use original media for the main image and zoom viewer.
- [x] 2.2 Preserve and verify gallery fallback, previous/next controls, thumbnail selection, zoom close behavior, keyboard focus, and no-media presentation.
- [x] 2.3 Preserve product-derived pricing, discount, stock, variant, quantity, wishlist, add-to-cart, buy-now, review loading, and FAQ behavior while adapting the page structure.

## 3. Mockup-aligned responsive presentation

- [x] 3.1 Implement the desktop composition from `detail-products/dekstop.png`, including gallery, product summary, options, purchase controls, support rail, benefits, detail navigation, rating, similar products, and footer hierarchy.
- [x] 3.2 Implement the tablet composition from `detail-products/tablet.png`, including the two-area product section, horizontal trust row, detail and rating panels, payment methods, and similar products.
- [x] 3.3 Implement the mobile composition from `detail-products/mobile.png`, including compact product content, option controls, benefit panel, collapsible detail rows, and sticky purchase actions.
- [x] 3.4 Add presentational static data for trust notes, benefits, payment-method display, and fallback specifications only where backend data is unavailable; do not fabricate transactional or review values.
- [x] 3.5 Reuse existing storefront tokens and UI primitives, add visible focus states, and verify long content does not create horizontal overflow at the three reference viewport classes.

## 4. Localization

- [x] 4.1 Add semantic Indonesian and English locale keys for all new headings, labels, actions, accessible names, accordion rows, trust notes, benefits, and empty states.
- [x] 4.2 Replace new template literals with locale lookups while leaving product-provided names and descriptions unchanged.

## 6. Detail query performance

- [x] 6.1 Add a composite lookup index for active products grouped by category and ordered by recency.
- [x] 6.2 Cache only the bounded related-product candidate IDs in the managed product-catalog group, with invalidation on product save/delete.
- [x] 6.3 Keep related product hydration and personalized/current pricing data fresh, and add regression coverage for cache invalidation.

## 5. Validation and traceability

- [x] 5.1 Run focused backend regression tests through `./vendor/bin/sail artisan test --filter ...` and record the result.
- [x] 5.2 Run frontend tests and `npm run build` after locale and page changes.
- [x] 5.3 Perform browser QA at desktop, tablet, and mobile viewport sizes against the supplied references, including original-image selection, zoom, accordion, sticky CTA, keyboard focus, and overflow checks.
- [x] 5.4 Run `openspec validate enhance-product-detail-gallery-and-responsive-ui --type change --strict` and verify all requirements map to completed tasks and tests.
