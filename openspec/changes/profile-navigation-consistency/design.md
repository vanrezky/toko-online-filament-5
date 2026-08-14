## Context

`Account/Profile.vue` renders overview, addresses, and profile settings as local state while orders open another route and wishlist is detached in a quick-links card. The desktop sidebar also becomes a long preamble before useful content on mobile. The existing account contracts and routes must continue to work.

## Goals / Non-Goals

**Goals:**

- Use one URL-backed navigation model for every customer account destination.
- Present account navigation as a desktop sidebar and compact mobile control without duplicating destination definitions.
- Establish one card hierarchy and consistent section headers.
- Retain clear touch access to profile-photo editing and separate session actions.
- Let authenticated customers change their password only after re-entering the current password.

**Non-Goals:**

- Change address ownership rules, orders, or wishlist business behavior.
- Redesign password recovery, introduce session revocation, or alter administrator credential management.
- Redesign unrelated storefront surfaces.

## Decisions

### Represent account destinations as shared URL-backed links

Every destination will use a route or account query parameter rather than mixed local-only state. The current URL determines active navigation, so refresh and browser history remain meaningful. A shared destination list will render both desktop and mobile navigation.

This is preferred over retaining local `activeSection` because local state cannot express navigation history or reliably restore the selected destination.

### Use responsive navigation rather than duplicate sidebars

Desktop keeps a profile summary beside the content. Mobile places a compact, horizontally scrollable destination list before the active content; no core destination is hidden. This preserves information architecture while avoiding a tall sidebar that pushes task content below the fold.

### Reduce card language to summary and content surfaces

The profile summary may retain a distinct surface. Content sections use a shared radius, spacing, border/elevation convention, and consistent headers. Decorative treatments are removed where they compete with account tasks.

### Make touch controls explicit

Photo editing uses a visible labelled/icon button with an accessible name rather than hover-only discovery. Logout belongs to its own session group after a visual separator.

### Change the password through a dedicated authenticated endpoint

The account page will expose Change Password as a URL-backed account section. Its form submits only `current_password`, `password`, and `password_confirmation` to a dedicated route behind the customer guard. The server validates the active credential with Laravel's `current_password:customer` rule and applies the shared `securePassword(8)` policy used by registration and reset flows. The Customer model's `hashed` password cast persists the accepted new password without exposing it to the client.

This is preferred over putting password fields in the profile update route because it creates a narrow credential contract, keeps profile updates independent, and allows tests to assert that an invalid credential leaves the password unchanged.

## Risks / Trade-offs

- [Query-param routing can create stale local state] → derive active state from the URL and preserve existing routes where possible.
- [Horizontal mobile navigation can overflow] → use a scrollable row with visible active state and touch-friendly targets.
- [Card consolidation can reduce visual distinction] → retain hierarchy through heading, spacing, and a reserved summary surface rather than arbitrary shadows.
- [Private-store address limits may be unclear] → retain and surface existing ownership/status explanations near address controls.
- [Credential-change errors could be mistaken for profile errors] → render the password form as a separate section with its own form state, field errors, and success reset.

## Migration Plan

Deploy with one authenticated customer password-update route. Existing account routes remain valid and no data migration is required; rollback removes the route and restores the prior Profile component.

## Open Questions

- None. Issue #38 scopes the account hub and customer password management without changing recovery or session-management behavior.
