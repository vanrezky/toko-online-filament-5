## Context

The storefront catalog query is currently assembled in `ProductController` and returns a `ProductSimpleResource` collection. It already supports search, category, price bounds, sorting, and twelve-item pagination, but the page turns pagination into a client-side “load more” list. Product variant attributes are exposed by `ProductResource` through `productVariants.variantAttributes.productAttribute` and `productAttributeOption`; the catalog query must use the same relationship model without loading full detail resources for every card.

The repository has product variant attribute relationships, but the catalog filter UI uses a deliberately small hardcoded common set for color, size, and gender. Brand is not part of the storefront filter scope. The change must not add a database schema or external dependency.

## Goals / Non-Goals

**Goals:**

- Keep filtering server-side and compatible with Inertia URL navigation.
- Use one normalized query contract for desktop and mobile controls.
- Reuse the existing global header search instead of duplicating a search field on the Products page.
- Support scalar, repeated-array, and comma-separated variant parameters safely.
- Avoid duplicate products when several variants match.
- Keep only the small common color, size, and gender lists in the frontend; do not fetch dynamic variant-option metadata for the catalog filter.
- Replace client-side accumulation with real numbered pagination.
- Keep current pricing, reseller, flash-sale pricing, wishlist, and ProductCard contracts intact.

**Non-Goals:**

- Adding product-level flags for free shipping or cashback.
- Changing checkout, pricing resolution, product detail, or database schema.
- Introducing a new state-management library or frontend dependency.

## Decisions

### Normalize filter input at the controller boundary

The controller will normalize query values before applying them: trim strings, coerce non-negative numeric bounds, validate sort/page-size against allowlists, and convert variant values into unique arrays. It will accept `variant_size=M`, repeated values, and comma-separated values so direct URLs and UI-generated URLs remain compatible.

Alternative considered: binding the page directly to raw request values. This was rejected because it duplicates validation between desktop/mobile controls and makes malformed URLs harder to handle safely.

### Resolve variant filters through `whereHas` relationship constraints

Each supported `variant_*` key will map to its product attribute name (`color` → `Warna`, `size` → `Ukuran`, `gender` → `Gender`) and constrain `productVariants.variantAttributes` by attribute and option. Multiple selected values within one attribute use OR semantics; different attribute groups use AND semantics. Brand and arbitrary dynamic variant keys are ignored. The base product query remains distinct at the product level.

Alternative considered: filtering the serialized `ProductResource` payload in Vue. This was rejected because pagination totals, result counts, and page links would be incorrect and it would load unrelated products.

### Use hardcoded common variant filter options

The frontend owns a small common option set for colors, sizes, and genders. The controller does not query or return variant-option metadata for the catalog filter.

Alternative considered: fetching all seeded or admin-created options in the controller. This was rejected because the requested storefront scope is a stable common set and dynamic variant options can be unnecessarily large.

### Use standard paginator navigation instead of load-more accumulation

The page will render the paginator data and links directly, preserving query strings with backend `withQueryString()`. Page-size changes will submit the same query with the first page selected. The page will no longer merge successive response pages into a local `allProducts` array.

Alternative considered: retaining load-more and adding numbered buttons around it. This was rejected because it creates two navigation models and does not match the approved mockup.

### Share one filter state model between desktop and mobile

The page will maintain a single reactive draft filter object. Desktop controls edit it directly; mobile edits the same shape inside the filter sheet and applies it through the same navigation function. Active chips derive from that state and remove one filter group without reconstructing query parameters manually.

Alternative considered: separate desktop and mobile forms. This was rejected because the two layouts could fall out of sync and produce different URLs for the same visible selections.

### Add focused backend and frontend regression coverage

Backend tests will cover each variant group, combined filter semantics, duplicate prevention, invalid values, query-string-preserving pagination, and page size. Frontend tests will cover filter metadata rendering, active-chip removal/reset, mobile sheet behavior, and numbered pagination. Existing search and ProductCard tests remain unchanged unless the new contract requires an assertion update.

## Risks / Trade-offs

- [Variant attribute names are user-configurable] → Match supported filter keys to the repository’s canonical color, size, and gender names; ignore brand and unsupported dynamic keys.
- [Many `whereHas` constraints can increase query cost] → Keep the product query selective, use `distinct` only when required by joins, inspect generated SQL, and add focused query coverage; no eager loading of full variants is needed for listing cards.
- [Existing bookmarked “load more” behavior changes] → Preserve all existing filter parameters and paginator URLs, and make the new numbered pagination the only catalog navigation model.
- [Existing dirty worktree contains user-provided mockups] → Keep the untracked design-reference files untouched and stage only Issue/OpenSpec/code files belonging to this change.

## Migration Plan

No database migration is required. Deploy the controller/resource metadata and page changes together. Rollback is a code revert; existing catalog URLs remain valid because the existing search, category, price, and sort parameter names are preserved.

## Open Questions

None.
