## Context

See `proposal.md` for the motivation and `specs/storefront-product-detail-experience/spec.md` for the observable contract.

The current detail resource exposes the first media item as an original `thumbnail`, but maps every media item in `images` to the `thumb` conversion. The product model generates `thumb` at 300x300. The detail page treats the string list in `images` as both gallery data and main-image candidates, so every selected image after the first can be rendered from a low-resolution asset.

The existing detail page already owns gallery state, option selection, quantity rules, wishlist, cart, buy-now, zoom, lazy review loading, and FAQ expansion. The implementation must preserve those behaviors while replacing the visual composition with the supplied desktop, tablet, and mobile references. The supplied reference files are user-owned design inputs and remain outside the application source contract.

## Goals / Non-Goals

**Goals:**

- Make original media the only source for the large gallery image and zoom viewer while retaining efficient thumbnail media for navigation.
- Add a related-product payload based on active products in the current product category, using the existing compact product resource and thumbnail contract.
- Implement one adaptive detail page whose composition changes at desktop, tablet, and mobile breakpoints to match the supplied references.
- Keep product-derived transactional values authoritative and isolate static presentation content to trust, benefits, and fallback specification rows.
- Keep new interface copy localized, keyboard-operable, and compatible with existing storefront design tokens.

**Non-Goals:**

- Changing R2 configuration, public domains, stored media, conversion dimensions, or migration behavior.
- Adding product-specification or benefit columns to the database.
- Changing pricing, flash-sale, reseller, stock, variant validation, cart, checkout, wishlist, or review business rules.
- Rebuilding unrelated storefront pages or replacing the shared header and footer globally.

## Decisions

### 1. Keep thumbnail and full-size media as separate resource fields

The detail resource will preserve `thumbnail` as the first original URL, expose `images` as original URLs for the full gallery, and add a parallel thumbnail field for navigation. Each thumbnail URL will fall back to its original URL when the conversion is not generated. The frontend will index both arrays together and will never use the thumbnail field for the main image or zoom viewer.

This keeps the change small and avoids changing `ProductSimpleResource`, which serves product cards, cart, and other compact contexts. A structured object per media item was considered, but it would create a larger contract change for no benefit while the existing page already uses parallel string values.

### 2. Query related products on the detail request

### 7. Cache only stable related-product candidates

The detail request caches the bounded related-product ID list for five minutes in a dedicated managed product-catalog cache group. The subsequent hydration query remains fresh for active status, stock, flash-sale state, wholesale pricing, reseller pricing, and media. Product save/delete events rotate only the product-catalog cache version, so catalog changes invalidate candidate lists without flushing unrelated application caches. The candidate query is supported by a composite products index on category, active status, created time, and ID.

The detail controller will select a bounded set of active products from the current product category, exclude the current product, eager-load only the media and compact data needed by `ProductSimpleResource`, and pass the collection as a separate Inertia prop. The section will be hidden when the query has no results.

Using real related products was chosen over hardcoded product cards so names, prices, availability, and links remain truthful. The query remains bounded and category-scoped rather than reusing the full paginated catalog query.

### 3. Keep page state in the existing detail page and extract only presentation boundaries

The existing page remains the owner of selected media, selected options, quantity, wishlist, purchase actions, reviews, zoom, and accordion state. Repeated visual units such as trust cards, benefits, option controls, and related product cards may be extracted into focused UI components when that reduces template complexity, but no component will duplicate business rules.

This preserves established Inertia and Vue behavior while allowing the new hierarchy to be implemented without introducing a second detail route or parallel mobile page.

### 4. Use breakpoint-specific composition rather than a separate responsive page

The same page will use the existing Tailwind token system and explicit breakpoint composition:

- Desktop: a three-area product section with the gallery, product summary, and support rail, followed by detail/rating and related-product sections.
- Tablet: a two-area product section, a horizontal trust row, and a two-column lower content section.
- Mobile: compact header-compatible content, a gallery-led product stack, horizontal option controls, collapsible detail rows, and a fixed bottom purchase bar.

The breakpoints will be driven by layout needs represented in the supplied references, not by duplicating the DOM into independent pages. Every composition will set overflow and minimum-width constraints so long names, controls, and price rows do not create horizontal scrolling.

### 5. Static content is limited to non-transactional presentation

Trust labels, benefit bullets, payment-method presentation, and fallback specification rows will be defined as localized presentation data. Product name, price, discount, stock, SKU, weight, variants, media, rating, and review count will continue to come from the existing payload and review endpoint. Static data will not be used to fabricate rating, stock, sales, or purchase outcomes.

### 6. Validate data contract and visual behavior separately

The backend regression test will assert that all original URLs are exposed and thumbnail URLs remain separate with conversion fallback. Frontend validation will cover the gallery selection contract and build. Browser QA will compare the actual page against each supplied reference at representative desktop, tablet, and mobile viewport sizes, including keyboard focus, sticky CTA reachability, gallery fallback, and no-horizontal-overflow checks.

## Risks / Trade-offs

- **[Existing consumers assume `ProductResource.images` contains thumbnails]** → Search confirms the resource is used by the detail flow and related tests; preserve compact resources and add the separate thumbnail field so consumers outside the detail page are not changed silently.
- **[Original R2 assets are larger than 300x300 conversions]** → Load original assets only for the selected main image and zoom viewer; keep navigation thumbnails lazy and use `decoding="async"`.
- **[A product may have missing or stale conversions]** → Fall back to the original URL for an individual thumbnail and show an intentional no-image state for an empty collection.
- **[Static specifications can look like product claims]** → Keep them in a clearly presentational section and exclude transactional or review assertions; use the approved mockup language only where the product has no stored equivalent.
- **[Three compositions can drift visually]** → Use one page state model, shared UI primitives, explicit viewport QA, and a focused diff review against all three reference files.
- **[Related-product query increases detail payload]** → Limit results to six active category peers, cache only stable candidate IDs, select/eager-load only fields used by the compact resource, and keep dynamic pricing and availability fresh.

## Migration Plan

1. Deploy the resource, controller, frontend, locale, migration, and test changes together. The migration adds only the composite lookup index; no R2 object migration is required.
2. Existing media rows continue to use their current R2 disk and paths. The new resource reads existing original files and generated conversions without rewriting them.
3. If the new detail page must be rolled back, revert the application commit. The database and R2 objects remain compatible with the previous page.

## Open Questions

None. The supplied mockups define the required visual direction, and missing non-transactional data is explicitly allowed to use static presentation content.
