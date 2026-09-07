## Why

The storefront homepage currently exposes the right commerce sections but presents them as a simpler, less cohesive composition than the approved Bristol Shop mockups. A more deliberate hierarchy across navigation, hero, discovery, products, trust content, newsletter, and footer will make the storefront easier to scan and give the brand a more premium feel without changing commerce behavior.

## What Changes

- Recompose the homepage around the approved warm-peach/orange visual direction from the user-provided references.
- Refine the storefront header with dynamic branding, primary navigation, search, account, and cart actions.
- Replace the current hero presentation with a rounded responsive hero composition that supports dynamic CMS content, primary/secondary CTAs, promotion messaging, trust points, and carousel indicators.
- Present popular categories as visual tiles while preserving existing category filter URLs.
- Add or refine promotional banners, featured products, all-products presentation, newsletter, trust/service strip, and multi-column footer composition using existing data and actions.
- Preserve product pricing, flash-sale pricing, wishlist, cart, voucher, newsletter, CMS, accessibility, and reduced-motion behavior.
- Keep the two mockups inside `.docs/design-references/homepage/` as reference-only project assets.

## Capabilities

### New Capabilities

- `storefront-homepage-experience`: Defines the responsive visual hierarchy and interaction behavior for the public storefront homepage.

### Modified Capabilities

<!-- No existing OpenSpec capability requirements are modified; this change adds the homepage experience specification. -->

## Impact

- Frontend page composition in `resources/js/frontend/pages/Home/Index.vue`.
- Existing homepage UI components under `resources/js/frontend/components/UI/` and the default header/footer templates.
- Frontend regression tests and browser visual QA.
- No backend, API, database, payment, permission, or external integration changes.
## Follow-up feedback scope

- Remove the legacy "For every day" collection story section and its unused presentation translations.
- Present the promotional carousel as three cards per view on desktop while retaining single-card mobile behavior.
- Split the hero into a 70/30 desktop composition with the hero image as the background of the content panel and service information in the secondary panel.
- Shared category data remains because `CategoryMenu` still consumes it; no dedicated controller query exists for the removed section.
