## Context

See `proposal.md` for the motivation and scope. The current application stores global website behavior in Spatie Laravel Settings through `GeneralSettings`. The Website settings page is a Filament `SettingsPage`, the login page receives shared values through `HandleInertiaRequests`, and Google/GitHub OAuth is handled by `SocialLoginController`. The existing private-store check already affects social-login presentation and must remain authoritative.

## Goals / Non-Goals

**Goals:**

- Add one persisted global flag that controls both presentation and endpoint enforcement.
- Preserve the current enabled behavior for existing installations through a default-enabled settings migration.
- Keep the configuration in the existing Website → Access settings surface.
- Prevent disabled OAuth endpoints from invoking Socialite or authenticating customers.
- Enforce the existing customer-registration toggle at both page and submission boundaries.
- Keep Indonesian and English admin copy consistent with the project's localization patterns.

**Non-Goals:**

- Provider-specific enablement, provider credential management, or adding providers.
- Changing the customer social-account model or OAuth linking rules.
- Replacing the existing private-store access behavior.
- Adding a separate registration setting or changing the meaning of the existing registration toggle.

## Decisions

### Use a boolean GeneralSettings value

Add `social_login_enabled` as a boolean setting and register it through a new settings migration with `true` as the default. This follows the existing settings architecture and avoids a new database table or configuration cache path. A provider list or nullable setting was rejected because the requested control is global and existing behavior must remain compatible.

### Enforce the flag at the OAuth controller boundary

Check the setting before creating a Socialite redirect or handling a callback. Disabled requests will be rejected with HTTP 404 so hidden functionality is not exposed and no OAuth provider call or customer mutation occurs. Relying only on hiding Vue buttons was rejected because direct requests would remain functional.

### Share only the availability flag to the login page

Expose the boolean through the existing Inertia `settings` payload and use it together with the existing private-store condition in `Login.vue`. This keeps the frontend presentation reactive to the same server-side source of truth without exposing OAuth credentials or broader settings data.

### Place the toggle in the existing customer access section

Add a Filament `Toggle` to `ManageWebsite` alongside registration, private-store, and terms settings. Use the existing admin translation namespace for the label and helper text, with Indonesian and English values. A separate settings page was rejected because the flag governs customer access and belongs with the existing access controls.

### Enforce customer registration at the controller boundary

`RegisterController` checks the existing `registration` setting before rendering or processing registration. Disabled GET requests redirect to the registration-closed page, while disabled POST requests are rejected before validation or customer creation. The login page receives the same flag through Inertia so its registration call to action does not advertise an unavailable flow. Relying only on hiding the link was rejected because direct requests would remain possible.

### Test behavior at both boundaries

Extend `SocialLoginTest` for enabled and disabled redirect/callback behavior and cover registration enforcement in the existing registration feature tests. Frontend tests and browser verification will confirm that controls and the registration call to action match the shared settings. Testing only the settings persistence was rejected because it would not protect endpoint security boundaries.

## Risks / Trade-offs

- [Existing settings data may not have the new key] → Use a default-enabled settings migration so existing installations retain current social-login behavior.
- [Frontend visibility could drift from endpoint enforcement] → Derive both from the same persisted flag and test each layer independently.
- [A user may have an old login page cached after disabling the setting] → Enforce the flag server-side on every redirect and callback request.
- [HTTP 404 may be less informative than a login redirect] → Treat disabled social login as an unavailable endpoint and keep the setting's helper text clear for administrators.

## Migration Plan

1. Deploy the settings migration, which adds `general.social_login_enabled` with value `true`.
2. Deploy the application changes and verify the Website → Access toggle and public login page.
3. If rollback is required, revert application code first; the extra settings key is harmless to the previous code and can remain for forward compatibility.

## Open Questions

None.
