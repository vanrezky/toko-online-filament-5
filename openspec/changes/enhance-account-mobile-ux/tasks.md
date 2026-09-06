## 1. Account shell and mobile composition

- [x] 1.1 Replace the mobile horizontal destination row with a full-width vertical account menu that reuses the existing localized destination groups and active-state logic.
- [x] 1.2 Add the mobile overview identity summary and arrange the account menu after the existing summary/activity content without changing data contracts or actions.
- [x] 1.3 Add compact mobile context and back-to-overview treatment for non-overview account destinations.
- [x] 1.4 Preserve desktop navigation, wallet feature gating, address-form active state, and existing account URLs.

## 2. Localization and frontend coverage

- [x] 2.1 Add any new mobile account context and accessibility labels to both Indonesian and English locale files.
- [x] 2.2 Update focused AccountShell and Profile frontend tests for mobile menu structure, active destinations, overview ordering, and non-overview context.

## 3. Validation and browser verification

- [x] 3.1 Run focused Vitest coverage for the account shell and profile page, then run the complete frontend test suite.
- [x] 3.2 Run the production frontend build and strict OpenSpec validation.
- [x] 3.3 Verify `/account` and representative non-overview account destinations at mobile and desktop viewports in the existing browser, including keyboard focus and no horizontal menu overflow.
- [x] 3.4 Run the Impeccable mechanical detector and `git diff --check`, then review the final diff against Issue #94 and the OpenSpec requirements.
