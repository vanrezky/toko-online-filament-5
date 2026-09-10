## Context

`AccountController::__invoke` currently loads the authenticated customer graph, province options, order count, recent orders, and optional balance history before returning `Account/Profile`. The primary profile and address data are required immediately; the other datasets serve secondary sections.

## Goals / Non-Goals

**Goals:**

- Move only secondary dataset resolution behind Inertia Deferred Props.
- Keep the initial Account Profile response usable and behaviorally compatible.
- Reuse the existing resources, regional service, order query, balance gate, and frontend components.

**Non-Goals:**

- Changing account authorization, validation, query semantics, or data models.
- Adding a new endpoint, dependency, cache layer, or client-side state manager.

## Decisions

- **Keep profile and addresses eager.** These are required by the profile shell and address management, so deferring them would make the page less usable and add no meaningful value.
- **Defer province options, order activity, and balance history.** These datasets are secondary and can use the existing controller response with a follow-up partial request.
- **Use the existing Inertia deferred-prop mechanism and one default deferred group.** This minimizes code and request fan-out while letting each Vue section own its loading fallback. Separate grouping is unnecessary until measurement shows the single follow-up request is a bottleneck.
- **Preserve existing query/resource construction inside deferred callbacks.** This keeps output and business rules stable; only when the work occurs changes.
- **Use existing frontend loading and empty-state patterns.** Do not add a new component or dependency for a short-lived fallback.

## Risks / Trade-offs

- [Secondary sections appear after the profile shell] → Keep visible accessible fallbacks and verify the core profile remains usable during the deferred request.
- [A deferred request can fail independently] → Preserve section-level fallback behavior and ensure profile/address actions do not depend on deferred props.
- [One default group serializes secondary data in one follow-up response] → Avoid premature request fan-out; revisit grouping only with before/after evidence.

## Migration Plan

No data migration is required. Deploy the controller and page changes together, verify the initial and deferred Inertia responses, and roll back the single commit if the deferred response or fallback behavior regresses.
