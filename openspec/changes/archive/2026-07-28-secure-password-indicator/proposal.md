## Why

When the website's `secure_password` setting is enabled, the storefront registration form currently provides no immediate explanation of the password rules enforced by the server. Customers discover those requirements only after submitting the form.

## What Changes

- Show a live password-requirements indicator on storefront registration when `secure_password` is enabled.
- Keep the indicator aligned with the existing server-side secure password rule: at least eight characters, with letters, numbers, and symbols.
- Keep the current registration validation and behavior unchanged when the setting is disabled.

## Capabilities

### New Capabilities

- `storefront-secure-password-guidance`: Live, configuration-gated password guidance for customer registration.

### Modified Capabilities

- None.

## Impact

- `app/Http/Controllers/Frontend/Auth/RegisterController.php` supplies the feature setting to the registration page.
- `resources/js/frontend/pages/Auth/Register.vue` renders the live guidance.
- `resources/js/locales/id.json` and `resources/js/locales/en.json` provide localized indicator text.
- Registration feature tests cover the setting and server-side validation behavior.
