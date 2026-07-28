## Context

`UserResource` shares its form schema between creation and editing, so password fields currently appear on both screens. The `User` model uses Laravel's `hashed` cast. The customer profile already provides the project pattern for a Filament header action that opens a modal and updates a password.

## Goals / Non-Goals

**Goals:**

- Keep password setup required when creating an administrator user.
- Remove password inputs from the standard edit form.
- Provide a dedicated, validated password-change modal from the edit page.

**Non-Goals:**

- Changing the password policy, authentication flow, or user roles.
- Changing passwords through the storefront.

## Decisions

- Use `hiddenOn('edit')` on the shared resource form fields so the create form retains its current required password inputs. This avoids splitting the profile schema or changing create behavior.
- Add a Filament header `Action` to `EditUser` with a separate schema containing `password` and `password_confirmation`. The action explicitly updates only `password`; the model cast hashes it.
- Reuse `securePassword()` and `same('password')` so modal validation matches the existing user and customer password workflows.
- Use localized labels and notification text in the existing `admin/user-resource` translation file.

## Risks / Trade-offs

- [A future password-policy change is added to only one form] → Both create and modal inputs call the shared `securePassword()` helper.
- [An invalid modal submission modifies the record] → The action updates the record only after Filament form validation succeeds.
- [The update is mistaken for a profile save] → The modal is opened by a clearly labeled, dedicated header action.
