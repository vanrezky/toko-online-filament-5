## Why

Homepage rendering currently resolves product, promotion, category, and slider data before the initial Inertia response can reach the browser. Deferring those independent datasets will let the shell and template render sooner while preserving the existing homepage composition and query semantics.

## What Changes

- Return expensive homepage datasets through grouped `Inertia::defer()` props, only when the active template needs them.
- Render a localized, accessible loading fallback for sections waiting on deferred data.
- Preserve section order, template preview behavior, links, product data, and empty-state behavior after deferred props resolve.
- Add focused frontend and backend regression coverage for deferred groups, conditional queries, and fallback composition.
- Record before/after initial response measurements during verification.

## Issue and Planning Status

- GitHub Issue: [#118](https://github.com/vanrezky/toko-online-filament3/issues/118)
- Classification: `chore`, `P2`, `STANDARD` with lightweight OpenSpec.
- Implementation branch: `chore/118-homepage-initial-render`, targeting `dev`.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `storefront-homepage-experience`: homepage data loading may be deferred, with usable section fallbacks and unchanged final composition.

## Impact

- Backend: `app/Http/Controllers/Frontend/HomeController.php` and homepage feature tests.
- Frontend: `resources/js/frontend/Pages/Home/Index.vue`, section registry, and homepage composition tests.
- No database schema, route, API payload contract, product query semantics, or new dependency changes.
