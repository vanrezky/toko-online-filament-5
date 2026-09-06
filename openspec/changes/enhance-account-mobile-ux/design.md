## Context

See `proposal.md` for the user-facing motivation. The current account page uses `AccountShell` for the desktop sidebar and a horizontally scrolling mobile destination row. `Profile.vue` owns the overview data and URL-backed section selection, while the profile and password forms are separate components. The change must remain frontend-only and preserve the existing Inertia routes, props, actions, and desktop composition.

## Goals / Non-Goals

**Goals:**

- Make the mobile account navigation readable and comfortable for touch input.
- Reuse the existing destination model and active-state logic so mobile and desktop cannot drift.
- Let `AccountShell` provide the mobile identity/context framing while `Profile.vue` continues to own account data and section content.
- Keep the visual language aligned with the existing warm, token-driven storefront design and the approved mobile mockup.
- Cover responsive structure, locale keys, and URL-backed navigation with focused tests and browser verification.

**Non-Goals:**

- No new account capabilities, server props, endpoints, schema changes, or business rules.
- No new profile fields, two-factor authentication, payment-method management, privacy pages, or help-center flows.
- No replacement of the existing desktop sidebar or redesign of populated account forms beyond responsive context framing.

## Decisions

### Keep one destination model for desktop and mobile

`AccountShell` will continue to build one localized `navigationGroups` model and reuse it for both navigation surfaces. This keeps feature-gated wallet visibility, route generation, and active-state behavior consistent.

Alternative considered: define a separate mobile menu list. Rejected because it would duplicate destination rules and could allow mobile and desktop to diverge.

### Use a vertical mobile menu in the overview flow

The mobile overview will place the account menu after the summary content as a full-width list, matching the mockup's progressive disclosure and eliminating the current horizontal-scroll row. Each row will have a large touch target, icon, label, and directional affordance where appropriate.

Alternative considered: replace the row with a hamburger drawer. Rejected for this scope because it hides destinations and adds an extra interaction for a page whose primary purpose is account navigation.

### Use compact context framing for non-overview destinations

When a mobile destination is not `overview`, the shell will show the active destination title and a back-to-overview affordance above the existing slot content. The overview will instead receive the identity summary and the full account-menu list. This gives each mobile destination the top-of-screen context shown in the mockup without duplicating its existing form headings.

Alternative considered: add back links separately to every account child component. Rejected because it would spread responsive navigation concerns across profile, password, balance, and address implementations.

### Keep user-facing copy in locale files

Any new context or accessibility label will be added under the active `labels.account` namespace in both locale files and consumed with `useI18n()`. Existing destination names will be reused.

Alternative considered: hardcode short labels in the shell. Rejected because the project requires Indonesian and English parity for frontend UI.

## Risks / Trade-offs

- [Long localized destination names can wrap on small screens] → use full-width rows with flexible label containers, preserve readable text size, and verify at 320px and 374px widths.
- [Moving the menu below overview content can make it less immediately visible] → keep the overview sections concise, use clear section separation, and verify that the menu remains reachable without excessive scrolling.
- [Shared shell changes can affect every account section] → add destination-level component tests and verify overview, wallet, settings, password, and address URLs in the browser.
- [The mockup contains capabilities not present in the application] → preserve existing data and actions and explicitly exclude new profile, security, payment, and help features.

## Migration Plan

No data or deployment migration is required. Deploy the frontend changes with the existing account routes and props. Rollback consists of restoring the prior mobile navigation row and removing the mobile-only framing while leaving the existing desktop shell and backend untouched.
