## Context

See [proposal.md](proposal.md) for scope and the current Issue-draft status. `Cart/Index.vue` already owns item selection, quantity updates, deletion, computed selected subtotal, and selected-ID checkout navigation. The references and subsequent user feedback are the visual authority. Voucher, shipping/payment methods, and security information are removed. Products/Show.vue and Home/Index.vue establish the surrounding storefront conventions.

This design document records the boundary between supplemental UI and real transactional cart state, the cached catalog recommendation strategy, plus responsive ownership of the checkout action.

## Goals / Non-Goals

**Goals:** Preserve existing cart behavior, provide relevant catalog-backed recommendations efficiently, and implement viewport-specific placement with shared state.

**Non-Goals:** Introduce transaction calculations, frequently-bought-together aggregation, new endpoint contracts, database schema changes, or a new shared storefront visual system.

## Decisions

1. **Scope the visual implementation to cart.** Use page-local styling and cart-specific components only where extraction improves readability or reuse within this page. Reuse the current template wrapper and existing visual primitives where compatible. A global header/theme rewrite would affect unrelated routes, so it is excluded; any cart-specific wrapper variation must leave other pages unchanged.

2. **Keep live cart state separate from supplemental data.** Existing props and handlers remain authoritative for item IDs, quantity, selection, prices, subtotal, and checkout navigation. Benefits remain presentation-only. Recommendations use real catalog resources but must not enter checkout payloads or alter transaction totals. The mockup's exact sample identities and prices are not requirements for live carts.

3. **Cache candidate IDs and hydrate fresh catalog data.** Derive up to three dominant categories from distinct cart lines, fetch small recent in-stock candidate pools through the existing `(category_id, is_active, created_at, id)` index, and cache only those IDs in the managed `product-catalog` group for five minutes. Exclude products already in the cart, interleave category pools, fill shortages from a cached global recent-product pool, and cap output at five. Hydrate selected IDs with current media, flash-sale, wholesale, reseller-price, and catalog-stat relations before serialization. Product save/delete invalidation continues to invalidate managed catalog caches.

4. **Use responsive layout with shared checkout state.** Desktop/tablet place the summary beside the items; mobile reorders the same sections and moves the primary checkout action into a fixed bottom bar. CSS placement is preferred over independent copies of the cart page to avoid selection drift. If separate button placements are necessary, only the active placement is visible and keyboard-accessible. Reserve bottom padding including the device safe area so the bar never hides the last recommendation or action. Device frames and operating-system chrome in mockup images are excluded from web content.

5. **Preserve backend-supported actions; keep unsupported actions local.** Reuse existing route contracts for quantity, deletion, and selected checkout. Recommendation cards navigate to real product detail pages; their save control remains local until persistence is separately specified. Missing image/description handling remains presentation-only and must not create cart records.

6. **Keep UI text in existing locales.** Add matching Indonesian/English keys and use existing currency formatting helpers. Product names, variant labels, and other real catalog content remain server data.

## Risks / Trade-offs

- Cached candidates can become unavailable → hydrate through an active, in-stock query on every request and tolerate fewer than five results.
- Cart-specific cache keys can grow without bound → cache shared category/global candidate pools, never a key containing the complete cart composition.
- Dense mockup rows and long real product names can overflow → verify long names/variants and actual viewport widths, adapting spacing without removing important controls.
- Mobile and desktop checkout placements can diverge → share the selected-ID handler and verify exactly one accessible primary action per viewport.
- A shared styling change could affect unrelated pages → scope selectors/components to cart and verify the existing wrapper remains compatible.
- Browser or service validation may be blocked → report the limitation and leave affected implementation/verification tasks unchecked.

## Migration Plan

No database migration is required. The existing related-product lookup index and managed product-catalog invalidation are reused. Rollback, if eventually needed, consists of reverting the scoped cart recommendation service/controller/frontend changes. Publication, merge, and deployment remain outside this change request.

## Storefront consistency after review

Reuse the existing `Button` for checkout, storefront color tokens (`primary`, `foreground`, `border`, `secondary`, `muted-foreground`), the shared sans font, and the storefront container convention (`container mx-auto max-w-7xl px-4`). Build the cart layout with Tailwind CSS 3 utilities in the Vue templates; keep `cart-*` classes only as stable test and browser-QA selectors, without a page-level stylesheet. The user explicitly removed checkout-only blocks, so mockup fidelity is assessed against that revised composition.
