## 1. Navigation foundation

- [x] 1.1 Define grouped account and shopping destinations from URL-backed routes/query state.
- [x] 1.2 Render the same destination model as desktop navigation and compact mobile navigation.
- [x] 1.3 Separate logout into a session action group while retaining its existing behavior.
- [x] 1.4 Add Change Password to the shared URL-backed desktop and mobile destination model.
- [x] 1.5 Extract shared account navigation and shell, then render it on the dedicated Wishlist route.

## 2. Account layout refinement

- [x] 2.1 Add consistent account-page and section headers for overview, profile settings, and addresses.
- [x] 2.2 Consolidate account content cards to the approved surface hierarchy.
- [x] 2.3 Replace hover-only profile-photo editing with a visible accessible touch control.
- [x] 2.4 Extract profile settings and password sections into focused account components; retain the existing address implementation for this change.

## 3. Customer password management

- [x] 3.1 Add a customer-guarded password-update endpoint with current-password, confirmation, and configured secure-password validation.
- [x] 3.2 Add a focused password form that clears sensitive fields after a successful update and renders field validation errors.
- [x] 3.3 Add regression coverage for successful change and rejected current-password, confirmation, and password-policy requests.

## 4. State preservation and validation

- [x] 3.1 Preserve existing profile, address, private-store, empty, and validation-error behavior.
- [ ] 4.2 Add or update frontend coverage for navigation state and responsive/account UI behavior.
- [x] 4.3 Run frontend tests, production build, Impeccable detector, strict OpenSpec validation, and scoped diff checks.
