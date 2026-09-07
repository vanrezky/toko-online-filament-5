## Why

Issue #97 addresses a contract gap between the template configuration maintained in `/admin/templates` and the storefront homepage. The admin already stores section ordering, activation, content, and colors, but the storefront still renders a mostly hard-coded composition and silently ignores several configured values. This change makes the active template the reliable source for the enhanced storefront while preserving existing commerce behavior.

## What Changes

- Normalize the admin color scheme into the CSS variable contract consumed by the storefront and Tailwind.
- Render supported homepage sections from the active template in configured order and omit inactive sections.
- Add a section registry and data mapping layer so section types map explicitly to storefront components and their data.
- Align existing admin fields with storefront requirements and add missing content keys identified by the enhanced homepage, including the hero content keys and supported section controls.
- Add a storefront preview path or mode that uses template settings before publication.
- Add regression coverage after the implementation work is complete for color mapping, section order/visibility, content mapping, fallbacks, and preview behavior.

## Capabilities

### New Capabilities

- `template-driven-storefront-rendering`: Resolve an active admin template into ordered, enabled storefront sections with a shared color and content contract.

### Modified Capabilities

- `storefront-homepage-experience`: The homepage composition, section visibility/order, color application, and preview output must follow the active template while preserving the existing responsive, accessibility, and commerce requirements.

## Impact

- Laravel template resolution and homepage data flow, including `HomeController` and related template models/resources.
- Inertia/Vue homepage rendering under `resources/js/frontend/pages/Home/Index.vue` and related section components/composables.
- Admin template content schema/forms and localization where new keys are required.
- CSS color-variable normalization and Tailwind theme integration.
- Storefront preview routing or mode and frontend/backend regression tests.
- No payment, order, authentication, or public commerce API contract changes are expected.
