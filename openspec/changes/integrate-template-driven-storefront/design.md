## Context

The active template is already loaded by `TemplateService` and returned through `TemplateResource` with ordered sections and content values. `HomeController` currently uses that data mainly to decide whether flash sale data is loaded, while `Home/Index.vue` renders the homepage in a fixed sequence. The existing color composable writes `--color-*` values while Tailwind consumes HSL-backed `--primary`, `--secondary`, `--foreground`, and related tokens. Template sections are also filtered to active rows during the normal active-template query, which is insufficient for an administrator preview of inactive sections.

The existing `storefront-homepage-experience` specification remains the visual and commerce baseline. This change adds the template contract without replacing the current product, cart, wishlist, newsletter, responsive, or accessibility contracts.

## Goals / Non-Goals

**Goals:**

- Establish one normalized storefront template payload for published and preview rendering.
- Make section order and activation state observable on the homepage for supported section types.
- Keep section-specific data loading in the backend data flow and section-to-component selection in an explicit registry.
- Normalize configured HEX colors to the HSL CSS variables consumed by Tailwind, including the text/foreground mapping.
- Add the missing admin field definitions needed by the enhanced storefront without requiring unrelated theme-editor redesign.
- Allow an authorized administrator to preview a selected template through the same renderer without changing published state.

**Non-Goals:**

- Replacing the existing template storage model or migrating historical templates wholesale.
- Making every currently registered section type renderable in this change; unsupported types remain safely skipped.
- Adding a sixth or larger color palette unless implementation proves an existing storefront token cannot be represented by the five configured colors.
- Changing payment, order, authentication, product, cart, wishlist, newsletter, or public API contracts.

## Decisions

1. **Use a normalized template payload at the storefront boundary.**

   `TemplateService`/the controller will provide the active or preview template as a stable payload containing template identity, resolved colors, and ordered section records with a `type`, `active` state where relevant, and key/value contents. The controller will remain responsible for loading only data required by sections that are enabled in the selected template. This keeps Eloquent relationships and admin field storage out of Vue components. A competing approach of having each Vue component search raw `template.sections` and fetch its own data would duplicate fallback logic and make section order difficult to guarantee.

2. **Use a section registry for rendering.**

   `Home/Index.vue` will iterate the normalized section list and resolve each supported type through an explicit registry mapping to a component and its data requirements. Unknown types are skipped with a safe fallback rather than causing a render failure. A legacy/default section list remains available when no active template exists so an incomplete template cannot blank the homepage. This is preferred over a long conditional chain because supported types and their mapping stay discoverable and can be tested independently.

3. **Correct the color contract at the CSS boundary.**

   The frontend will normalize valid HEX values to HSL channel strings before assigning the variables consumed by Tailwind (`--primary`, `--secondary`, `--accent`, `--background`, `--foreground`, and their required foreground variants). The admin text color maps to `foreground`; it will not be discarded or renamed ad hoc. Missing/invalid values use the existing safe defaults. Keeping conversion at one boundary avoids changing every existing Tailwind class or requiring administrators to enter HSL values.

4. **Extend existing template field definitions, not the section storage model.**

   The enhancement will first align the existing keys (`show_all`, `limit`, `show_discount`, `show_timer`, `category_id`, `columns`, `show_load_more`, and `bg_style`) with the storefront mapping, then add only the missing hero/content keys required by the current enhanced homepage. Field labels, defaults, and translations will be updated alongside their definitions. A new generic JSON schema or an unrelated admin editor is deferred because the current section/field/content relations already support the required values.

5. **Preview through a separate authorized read path using the same renderer.**

   Preview will identify a selected template, authorize the administrator, load all of its sections including inactive ones for preview context, and pass an explicit preview flag to the same storefront page/section registry. Published customer requests will continue to resolve only the active template and its cache. Preview will not write activation state or reuse the active-template cache. A second preview-only Vue composition is rejected because it would allow published and preview output to drift.

6. **Preserve cache invalidation boundaries.**

   Active-template and color cache invalidation will remain tied to template/section/content updates. Preview reads will bypass the active-template cache or use a template-id-scoped cache that cannot overwrite it. This avoids stale published output while keeping normal homepage requests efficient.

## Risks / Trade-offs

- **[Risk] Existing templates lack newly required keys.** → Define safe defaults and add field definitions/seed updates before enabling the corresponding controls; verify legacy templates render without errors.
- **[Risk] Filtering active sections too early hides data needed by preview.** → Add an explicit preview loading path that includes inactive sections while keeping published queries filtered.
- **[Risk] HEX-to-HSL conversion changes perceived colors.** → Keep the five admin values as source-of-truth, add focused conversion tests, and verify representative values in the browser against the configured palette.
- **[Risk] Unknown or malformed section types create blank space or break order.** → Registry resolution returns a null renderer for unsupported types and the normalized list is compacted before rendering.
- **[Risk] Hard-coded data loading remains after section ordering becomes dynamic.** → Make controller data loading derive from the normalized enabled section types and cover the mapping with regression tests.

## Migration Plan

1. Implement color normalization and the normalized storefront template payload behind the current homepage behavior.
2. Introduce the section registry and migrate supported homepage sections in the approved order `1 → 2 → 3`.
3. Align and add admin field keys, preserving defaults for existing records (`4`).
4. Add the authorized preview path using the same payload and renderer (`6`).
5. Add and run focused backend/frontend regression coverage last (`5`), then run the production frontend build and browser verification.

No destructive data migration is planned. Rollback is a code rollback; existing template records remain readable because missing keys use defaults and unsupported sections are skipped.

## Open Questions

- None that change the approved behavior or architecture. The exact route name for the authorized preview can be selected during implementation as long as it uses the same renderer, does not mutate published state, and is covered by authorization tests.
