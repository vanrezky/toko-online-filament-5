## Context

Issue #95 requests a frontend-only homepage redesign based on the user-provided Bristol Shop mockups. `resources/js/frontend/pages/Home/Index.vue` already composes the main storefront sections and receives dynamic props for products, categories, filters, template content, sliders, and flash sales. Existing UI components own most section behavior, while the default header and footer own global storefront navigation and newsletter submission.

The implementation must preserve current routes and data contracts, use the established Tailwind tokens in `resources/css/app.css`, keep `Space Grotesk`, and treat `.docs/design-references/homepage/` as reference-only. No backend or schema work is needed.

## Goals / Non-Goals

**Goals:**

- Establish a warm, premium, restrained storefront hierarchy matching the approved reference composition.
- Keep content and commerce behavior dynamic and reusable rather than baking the mockup into the page.
- Make the first viewport persuasive while keeping category and product discovery fast on mobile.
- Verify the result with frontend tests, build checks, browser inspection, and the Impeccable detector.

**Non-Goals:**

- Do not replace CMS content, product data, prices, routes, or backend behavior.
- Do not render the reference screenshots as production UI or add a new external font/image dependency solely to imitate them.
- Do not redesign unrelated authenticated, checkout, admin, or product-detail pages.

## Decisions

### Use a component composition, not a screenshot-shaped page

Refine the existing homepage section components and default templates, with `Index.vue` remaining the composition boundary. This keeps section behavior testable and preserves the existing prop contracts. A single monolithic homepage would make the dynamic fallbacks and commerce actions harder to verify.

### Use a token-led warm-peach storefront world

Use the existing `primary`, `secondary`, `background`, `foreground`, `muted`, `border`, and `ring` tokens, adding only narrowly scoped utility styling if a reference treatment cannot be expressed with current tokens. The visual direction is restrained: orange carries actions and selected states, cream/warm secondary surfaces carry promotion, and charcoal remains the reading color. This avoids hardcoded brand colors and keeps theme behavior intact.

### Make the hero a responsive content composition

The hero will be a rounded, contained composition with dynamic title, subtitle, badge, CTA link, and optional image. Supporting trust points and indicators will be rendered as content around the hero without inventing claims when CMS values are absent. Existing hero fallback behavior remains the source of truth.

### Preserve product behavior by refining existing product primitives

Use the existing product card and section components for badges, pricing, wishlist, rating, cart, and flash-sale states. Visual changes will improve hierarchy and spacing while keeping route generation, callbacks, and price helpers unchanged. This is safer than introducing a parallel product-card implementation.

### Treat desktop and mobile as deliberate compositions

Desktop will use the reference's wide navigation, contained hero, category tile row, banner grid, and product rail/grid. Mobile will collapse those groups into readable stacked sections, retain touch-sized actions, and avoid forcing the reference's desktop density into horizontal page overflow. Existing mobile menu and focus patterns remain in use.

### Validate behavior and rendered appearance separately

Frontend tests will cover composition and conditional data states; `npm run build` will cover production compilation; browser QA at desktop and mobile viewports will verify visual hierarchy, overflow, interaction, and responsive behavior. The Impeccable mechanical detector will run once after UI changes, and its findings will be addressed without replacing rendered browser evidence.

## Risks / Trade-offs

- [Reference imagery implies product photography that may not exist in CMS] → Keep production rendering data-driven, reuse verified existing imagery/content, and use the stored screenshots only as visual references.
- [Changing shared header/footer can affect other pages] → Scope style changes to the default storefront template, inspect representative non-home routes, and preserve navigation/search/newsletter contracts.
- [Dense mockup composition may become cramped on mobile] → Define explicit mobile stacking and spacing rules, then verify at the in-app browser mobile viewport.
- [Existing template content may be sparse] → Retain current localized fallbacks and avoid introducing unverified commercial claims.

## Migration Plan

No database or deployment migration is required. Implement the frontend changes on `dev`, run frontend tests/build and browser QA, then revert the homepage component changes as a single code change if visual or behavioral validation fails. The reference images remain documentation assets and do not enter the production asset pipeline.

## Direction Contract

**THESIS:** Make the homepage feel like a calm, premium shopping partner with a clear path from offer to product, refusing generic marketplace clutter.

**OWN-WORLD:** White and warm-cream canvases, orange actions, charcoal Space Grotesk, soft peach surfaces, fine borders, rounded hero/promo shapes, and precise icon-led trust details.

**STORY:** The visitor understands the store's everyday value, sees how to browse by category, trusts the service, and acts on a product or promotion.

**FIRST VIEWPORT:** A clean dynamic header leads to a contained peach hero with strong headline, dual CTAs, promo badge, supporting imagery, trust points, and a restrained indicator row.

**FORM:** A contained editorial storefront composition, chosen as the direct reference-led structure because the user supplied a precise mockup rather than an open-ended visual exploration.
## Follow-up feedback decisions

- The hero uses a 70/30 desktop grid: the 70% panel owns the image background, readable overlay, title, description, promotion, and CTAs; the 30% panel owns the service information. Mobile stacks the panels.
- The promotional carousel uses one card on mobile, two at intermediate widths, and three at desktop widths with measured gaps owned by the Embla container.
- `StoreStorySection` is removed because its category fallback duplicates `CategoryMenu`; shared category data remains required by the menu and filters. No backend controller or migration is removed because none is dedicated to the section.
### Keep newsletter and footer data-driven

Newsletter presentation follows the approved footer reference while preserving CMS overrides except for the legacy seeded copy, which is replaced by the localized reference copy. Footer social and contact values come only from the existing `GeneralSettings` fields shared through Inertia. Unconfigured channels are omitted rather than rendered as dead or fabricated links; payment methods use local text marks because no verified payment-logo asset set exists in the project.
