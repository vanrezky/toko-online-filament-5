## Why

GitHub Issue: #122

The account profile response currently resolves profile, regional, order, and balance data before the page can render. Deferring secondary datasets will make the account shell usable sooner while preserving the existing profile and address behavior.

## What Changes

- Keep customer profile, addresses, account settings, permissions, and balance visibility available in the initial response.
- Defer province options, recent orders, and balance history through Inertia Deferred Props.
- Render accessible loading fallbacks for deferred account sections.
- Preserve existing regional queries, order limits/order, balance gating, empty states, and authorization behavior.

## Capabilities

### New Capabilities

- `storefront-account-profile-performance`: Account Profile renders core account data immediately and loads secondary regional, order, and balance data asynchronously.

### Modified Capabilities

## Impact

- `app/Http/Controllers/Frontend/AccountController.php`
- `resources/js/frontend/Pages/Account/Profile.vue`
- Account Profile frontend tests and feature tests for Inertia props.
- No database schema, route, API, or dependency changes.
