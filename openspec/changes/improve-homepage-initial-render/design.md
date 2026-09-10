## Context

The homepage controller already determines the active template and section configuration before building the response. It currently resolves the product, category, slider, and flash-sale queries in that same request, while the Vue page resolves all section components from the returned props. Inertia 2 and the installed Vue adapter support deferred props and a `Deferred` component, so the change can use the existing stack without a new dependency or endpoint.

## Goals / Non-Goals

**Goals:**

- Keep template configuration, filters, preview state, color scheme, and the initial shell available immediately.
- Move only the expensive, section-dependent datasets behind deferred callbacks.
- Keep independent datasets in separate groups so the browser can request them concurrently.
- Reuse the existing section registry and wrap only data-dependent sections with the native deferred fallback mechanism.
- Preserve current query limits, resources, pricing/stat enrichment, cache behavior, section order, and empty states.

**Non-Goals:**

- No new API route, polling mechanism, client-side data fetch service, cache redesign, or dependency.
- No changes to template selection, product semantics, SEO metadata, authentication, or checkout behavior.
- No redesign of section skeletons beyond a small accessible loading fallback.

## Decisions

1. **Resolve template metadata eagerly; defer datasets.** The active template and its sections determine which datasets are needed and the query limits, so they remain available to construct the response. Product, category, slider, and flash-sale work moves into callbacks. This avoids evaluating expensive queries during the initial response without duplicating template-selection logic. A fully client-driven homepage configuration would require a new endpoint and duplicate server rules, so it is rejected.

2. **Use one deferred group per independent dataset.** Products, categories, sliders, and flash sales do not depend on each other, so each gets its own group. This allows separate partial reloads to run concurrently while keeping the products prop shared by both product sections. One shared group for every dataset would reduce requests but serialize unrelated work, so it is rejected.

3. **Annotate dependencies in the existing section registry.** Each registry entry that needs deferred data declares its prop key. `Home/Index.vue` uses the installed `Deferred` component inline and supplies one localized status fallback. This keeps section ordering and component prop construction in one existing registry and avoids a new wrapper component.

4. **Treat unresolved flash sales as pending, not absent.** The flash-sale registry entry remains present while its prop is missing, allowing the fallback to display; it is removed after the deferred result is explicitly null. Other optional sections retain their current availability rules.

5. **Keep failed loads non-blocking.** No broad exception swallowing is added to the controller. A failed partial request leaves the affected `Deferred` fallback in place while the already-rendered shell and unrelated sections remain interactive; backend errors remain visible to normal application diagnostics.

## Risks / Trade-offs

- [More partial requests] Separate groups can add request overhead → only create groups for datasets needed by active sections and allow the client to issue them concurrently.
- [Layout movement] Sections appear after the shell → use a stable, full-width status fallback at each section position.
- [Failure fallback can remain visible] The native deferred component has no page-level retry UI → keep the shell and unrelated content usable; add retry only if product requirements later require recovery controls.
- [Preview behavior] Preview and public homepage share the same deferred callbacks → preserve the existing `renderHome` inputs and query semantics, then verify both routes.

## Migration Plan

No migration is required. Deploy the controller and page changes together. Rollback is a normal code rollback; the previous eager response path remains the only behavior restored, with no persisted data or cache invalidation required.

## Open Questions

None.
