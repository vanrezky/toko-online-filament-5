## Why

Closes #112. The product detail gallery currently uses the original media only for the first main image and uses the 300x300 `thumb` conversion for subsequent main images, which visibly reduces quality. The page also needs a mockup-aligned product detail experience that preserves real product behavior across desktop, tablet, and mobile layouts.

## What Changes

- Separate original media URLs for the main gallery from `thumb` URLs used by thumbnail navigation.
- Update the product detail presentation so selecting any gallery item, navigating, and zooming uses the original asset.
- Redesign the desktop product detail composition to match the supplied reference, including gallery, product summary, trust cards, benefits, detail tabs, rating, similar products, and footer hierarchy.
- Redesign the tablet composition to match the supplied reference, including the two-column product area, benefit row, detail and rating panels, payment methods, and similar products.
- Redesign the mobile composition to match the supplied reference, including the compact header, gallery controls, option selectors, benefit panel, accordion sections, and sticky purchase actions.
- Keep product name, pricing, discount, stock, category, SKU, weight, media, variants, and reviews sourced from existing application data.
- Add static presentation content only for unavailable non-transactional metadata such as product benefits, trust notes, and fallback specifications.
- Preserve existing cart, buy-now, wishlist, quantity, variant, review, and zoom behavior.
- Add or update frontend localization keys for new user-facing labels in Indonesian and English.

## Capabilities

### New Capabilities

- `storefront-product-detail-experience`: Defines high-quality product media presentation and mockup-aligned product detail layouts across desktop, tablet, and mobile viewports.

### Modified Capabilities

None.

## Impact

- `app/Http/Resources/ProductResource.php` will expose original and thumbnail media URLs as separate presentation fields.
- `resources/js/frontend/pages/Products/Show.vue` and potentially focused reusable UI components will receive the new gallery, product information, trust, benefit, detail, rating, similar-product, and responsive layout composition.
- `resources/js/locales/id.json` and `resources/js/locales/en.json` will receive matching labels for new UI copy.
- Existing product listing and cart thumbnail contracts must remain unchanged.
- No R2 configuration, object migration, database schema, pricing rule, stock rule, checkout rule, or review data mutation is in scope.
- Validation will include focused backend tests through Laravel Sail, frontend build/tests, OpenSpec validation, and browser QA at the supplied desktop, tablet, and mobile viewport compositions.
