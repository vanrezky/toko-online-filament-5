## 1. Color scheme contract

- [x] 1.1 Trace the active template color payload from `TemplateService`/`TemplateResource` to the storefront and document the five supported admin fields and safe defaults.
- [x] 1.2 Implement one HEX-to-HSL normalization boundary that maps primary, secondary, accent, background, and text to the runtime CSS variables consumed by Tailwind.
- [x] 1.3 Update the color composable/template wrapper integration so configured foreground and fallback values are applied consistently without breaking existing pages.
- [x] 1.4 Verify representative configured, missing, and invalid color values with focused tests or equivalent deterministic checks.

## 2. Template-driven section rendering

- [x] 2.1 Extend the homepage backend data flow to expose the normalized active template, ordered supported sections, section contents, and only the data needed by enabled sections.
- [x] 2.2 Preserve existing homepage data contracts and fallback composition when no active template or incomplete section configuration is available.
- [x] 2.3 Update the homepage renderer to iterate supported template sections in configured order and omit inactive sections without leaving layout gaps.
- [x] 2.4 Verify the published homepage still preserves product, category, flash-sale, wishlist, cart, voucher, newsletter, responsive, and accessibility behaviors.

## 3. Section registry and data mapping

- [x] 3.1 Define an explicit registry for supported section types and their storefront components/data requirements.
- [x] 3.2 Map existing hero, category menu, featured products, flash sale, products grid, newsletter, carousel, voucher, and trust content into the registry without duplicating section lookup logic.
- [x] 3.3 Make unknown or malformed section types safe to skip and compact the rendered section list.
- [x] 3.4 Verify the registry mapping against configured section order and the existing homepage reference composition.

## 4. Admin field-key enhancement

- [x] 4.1 Inventory current template section field definitions, content records, defaults, and translations against the enhanced storefront requirements.
- [x] 4.2 Align existing controls (`show_all`, `limit`, `show_discount`, `show_timer`, `category_id`, `columns`, `show_load_more`, and `bg_style`) with the section registry data mapping.
- [x] 4.3 Add and localize missing hero/content keys (`eyebrow`, `badge`, `secondary_text`, `secondary_link`, `promo_label`, `promo_value`, and `trust_points`) with safe defaults.
- [x] 4.4 Confirm existing templates remain readable and admin edits invalidate the relevant template/color caches.

## 6. Storefront template preview

- [x] 6.1 Add an authorized preview entry point for selecting a template without changing the published active template.
- [x] 6.2 Load preview sections including inactive sections where needed, bypass active-template cache mutation, and pass an explicit preview state to the shared renderer.
- [x] 6.3 Reuse the same section registry, content fallbacks, and color normalization for preview and published storefront output.
- [x] 6.4 Verify unauthorized access is denied and preview changes remain isolated from normal customer requests.

## 5. Regression coverage and release verification

- [x] 5.1 Add backend regression coverage for active-template resolution, section order/activation, content fallbacks, color normalization, and preview isolation.
- [x] 5.2 Add frontend regression coverage for registry rendering, unknown-section handling, runtime color variables, and preserved homepage actions.
- [x] 5.3 Run the canonical Sail backend tests for the focused coverage and run frontend tests/build using the repository commands.
- [x] 5.4 Verify desktop, tablet, and mobile storefront output in the existing browser environment, including no horizontal overflow and correct configured section order.
- [x] 5.5 Review acceptance criteria against Issue #97 and OpenSpec, then record validation results for the pull request.
