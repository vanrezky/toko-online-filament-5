## 1. Preparation and homepage composition

- [x] 1.1 Inspect current homepage props, settings, CMS section fallbacks, routes, product actions, and shared template contracts against the approved references.
- [x] 1.2 Update `Home/Index.vue` composition to the reference-led section order without removing conditional flash-sale, filtered-product, voucher, slider, or newsletter behavior.

## 2. Storefront visual implementation

- [x] 2.1 Refine the default header with the dynamic logo, primary navigation, search, account, cart, active states, and responsive mobile menu hierarchy.
- [x] 2.2 Rework the homepage hero into the contained warm-peach composition with dynamic CMS content, responsive imagery, CTA pair, promotion treatment, trust points, and indicators.
- [x] 2.3 Rework category presentation into visual popular-category tiles while preserving existing category filter links and active state.
- [x] 2.4 Refine promotional banners, featured products, product cards, and all-products content using existing product data, pricing, wishlist, rating, cart, and flash-sale behavior.
- [x] 2.5 Add or refine the newsletter, trust/service strip, and multi-column footer composition using existing submission behavior and dynamic settings/menu content.
- [x] 2.6 Apply token-led Space Grotesk typography, warm orange/charcoal/cream palette, responsive spacing, focus states, alt text, and reduced-motion behavior across the changed UI.

## 3. Regression coverage

- [x] 3.1 Add or update frontend tests for homepage section order, conditional sections, category filtering, and preserved product/action states.
- [x] 3.2 Add test coverage for responsive/accessibility-sensitive markup where behavior can be asserted without a browser.

## 4. Validation and visual QA

- [x] 4.1 Run focused frontend tests and the full `npm run test` suite.
- [x] 4.2 Run `npm run build` and `git diff --check`.
- [x] 4.3 Run the Impeccable detector once against changed UI targets and address actionable findings.
- [x] 4.4 Inspect the homepage in the Codex browser at desktop and mobile viewports, verify key navigation/product/newsletter interactions, and correct visual or overflow regressions.
- [x] 4.5 Re-run validation after browser-driven fixes and confirm OpenSpec acceptance criteria are satisfied.
## 5. Follow-up visual feedback

- [x] 5.1 Remove the legacy collection story section, unused locale strings, and frontend test references while preserving shared category data used by `CategoryMenu`.
- [x] 5.2 Recompose `HeroSection` into a 70/30 desktop image-background/service layout with a stacked mobile fallback.
- [x] 5.3 Configure `HeroCarousel` for one/two/three visible cards at mobile/intermediate/desktop widths without horizontal page overflow.
- [x] 5.4 Update regression coverage and run frontend tests, build, diff checks, detector, and desktop/mobile browser QA for the revised homepage.
- [x] 5.5 Align `ProductCard` with the approved reference hierarchy, including compact image proportions, colored badges, rating placement, and cart affordance.
- [x] 5.6 Re-run regression tests, build, detector, diff checks, and browser QA after product-card feedback.
- [x] 5.7 Align newsletter, trust strip, and footer with the approved Bristol footer reference while preserving subscription behavior and configured settings data.
- [x] 5.8 Verify desktop/mobile closing-section layout, configured contact/social rendering, no horizontal overflow, tests, build, detector, and OpenSpec validation.
