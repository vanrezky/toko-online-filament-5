## Why

Issue #94: the customer account navigation is difficult to use on a narrow mobile viewport because the current destination pills are presented in a horizontally scrolling row. The account overview also does not expose the profile and account-menu hierarchy shown in the approved mobile mockup, making important destinations less discoverable.

## What Changes

- Replace the cramped mobile account destination row with a clear, touch-friendly account menu while preserving the existing URL-backed destinations and active-state behavior.
- Add a mobile account identity summary and organize the overview content in the mockup's order: identity, balance and order summaries, default address, recent activity, and account menu.
- Add mobile context/back treatment for non-overview account destinations without changing their forms, data, or submit actions.
- Use one shared section title and description for each account destination so content cards do not repeat their page heading; keep the browser document title unchanged.
- Keep logout in a distinct session-action group in the desktop sidebar and mobile overview menu, without adding logout actions to non-overview destination headers.
- Preserve the existing desktop account shell and responsive behavior outside the affected mobile composition.
- Keep all new visible copy localized in Indonesian and English and add focused frontend/browser regression coverage.

## Capabilities

### New Capabilities

- `customer-account-mobile-ux`: Responsive mobile account hierarchy, touch-friendly navigation, overview composition, and destination context for authenticated customers.

### Modified Capabilities

- None.

## Impact

- Vue account shell, account profile page, and account-related UI components under `resources/js/frontend/`.
- Indonesian and English frontend locale files if additional semantic labels are required.
- Frontend unit tests and mobile browser verification; no backend, API, database, payment, authorization, or data-model changes.
