## Context

The account page already derives its active section from the `section` query parameter and receives the authenticated customer's balance and a limited recent balance history from `AccountController`. `AccountShell` owns both desktop and mobile destination lists. Register uses Inertia links for the two legal pages, while addresses, orders, and wishlist currently use different empty-state surfaces.

## Goals / Non-Goals

**Goals:**

- Extend the existing URL-backed account destination model with `balance`.
- Reuse the existing authenticated account props and balance ledger; keep the account overview as a summary.
- Make legal links ordinary safe external-tab anchors while retaining the existing route URLs and consent form behavior.
- Use the Wishlist empty-state surface as the visual source of truth for addresses and orders.
- Keep Indonesian and English visible copy aligned.

**Non-Goals:**

- No balance calculation, ledger mutation, checkout payment, authorization, schema, or pagination changes.
- No legal content or registration validation changes.
- No redesign of populated address, order, or wishlist layouts.

## Decisions

### Use the existing account page section for wallet balance

The wallet destination will use the existing `frontend.account` route with `section=balance`. This preserves URL-backed refresh/history behavior and avoids a duplicate controller or page while still giving customers a dedicated menu destination. The balance menu is rendered only when `balanceEnabled` is true; direct access to another section does not expose balance data when the feature is disabled.

Alternative considered: a new `/account/balance` route and page. This would duplicate the account shell and controller data contract without providing additional behavior.

### Keep current balance data contract

The implementation will use `user.balance` for the current amount and the existing `balanceHistory` prop for ledger entries. The controller already scopes both values to the authenticated customer, so no new endpoint or query contract is needed.

Alternative considered: adding a separate API request or paginated ledger endpoint. That is outside the requested UX change and would introduce unnecessary loading and authorization surface.

### Convert legal links to safe browser anchors

The two existing legal destinations will render with `target="_blank"` and `rel="noopener noreferrer"`. Their generated route URLs and localized labels remain unchanged. This preserves the registration page while preventing the new tab from retaining an opener reference.

### Reuse one empty-state class contract

Addresses and orders will adopt the Wishlist empty-state container and typography classes, while retaining their own icons, messages, and existing actions. The Wishlist content remains the visual reference and remains functionally unchanged.

## Risks / Trade-offs

- [The current history prop is limited to recent entries] → retain the existing data contract for this scoped UX change and avoid implying a new full-history API.
- [A query-string destination can be manually entered while disabled] → render wallet content only when `balanceEnabled` is true and keep the controller's customer guard unchanged.
- [Copy and class changes can drift between locales or pages] → update both locale files and add focused frontend assertions.

## Migration Plan

Deploy as a frontend/account navigation change with no data migration. Rollback removes the `balance` destination and restores the overview history block and prior empty-state classes.

## Open Questions

None.
