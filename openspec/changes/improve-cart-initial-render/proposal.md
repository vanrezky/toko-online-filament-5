# Improve Cart Initial Render

## Why

The cart page currently resolves catalog recommendations before the initial Inertia response reaches the browser. The cart itself is actionable, but an unrelated recommendation query delays the first render.

## What Changes

- Return authenticated-cart recommendations through one grouped `Inertia::defer()` prop.
- Keep cart loading, flash-sale synchronization, cart serialization, totals, stock data, and checkout data eager.
- Preserve the existing `CartRecommendationService`, candidate filtering, resource shape, and guest/empty-cart behavior.
- Render a localized accessible loading fallback until recommendations resolve.
- Add focused backend and frontend regression coverage and record response-performance evidence during verification.

## Issue and Planning Status

- GitHub Issue: [#121](https://github.com/vanrezky/toko-online-filament3/issues/121)
- Classification: `chore`, `P2`, `STANDARD` with lightweight OpenSpec.
- Implementation branch: `chore/121-cart-initial-render-deferred-recommendations`, targeting `dev`.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `storefront-cart-experience`: recommendation data may load asynchronously while the actionable cart shell renders immediately.

## Impact

- Backend: `app/Http/Controllers/Frontend/CartController.php` and cart recommendation feature tests.
- Frontend: `resources/js/frontend/Pages/Cart/Index.vue` and cart composition tests.
- No database schema, route, recommendation algorithm, API contract, or new dependency changes.
