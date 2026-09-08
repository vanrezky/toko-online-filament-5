## 1. Settings and admin configuration

- [x] 1.1 Add `social_login_enabled` to `GeneralSettings` with a default-enabled settings migration.
- [x] 1.2 Add the Login Sosial toggle to the Website → Access section of `ManageWebsite`.
- [x] 1.3 Add Indonesian and English admin translations for the toggle label and helper text.

## 2. Storefront presentation and enforcement

- [x] 2.1 Share the persisted social-login availability flag through the existing Inertia settings payload.
- [x] 2.2 Update the storefront login page to render social-login controls and separator only when the flag is enabled and the store is public.
- [x] 2.3 Enforce the flag at the social-login redirect and callback controller boundary, rejecting disabled requests before OAuth or customer mutations.

## 3. Automated verification

- [x] 3.1 Extend `SocialLoginTest` for enabled-flow compatibility and disabled redirect/callback rejection.
- [x] 3.2 Add or update frontend coverage for social-login control visibility when the shared setting changes.
- [x] 3.3 Verify settings persistence and preserve the existing private-store restriction behavior.

## 4. Validation and delivery traceability

- [ ] 4.1 Inspect Docker/Sail availability and run focused backend tests through `./vendor/bin/sail artisan test`.
- [x] 4.2 Run frontend tests and `npm run build`.
- [x] 4.3 Verify the admin toggle and storefront login and registration states in the browser at the configured local URL.
- [x] 4.4 Run strict OpenSpec validation and review traceability against Issue #107.
