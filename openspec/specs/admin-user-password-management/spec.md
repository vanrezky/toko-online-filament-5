## Purpose

Provide a dedicated, validated password-change flow for administrator-managed users.

## Requirements

### Requirement: Dedicated administrator password update
The system SHALL provide a dedicated password-change action on the administrator user edit page. The standard edit form SHALL NOT display password or password-confirmation inputs, while the user creation form SHALL continue to require both inputs.

#### Scenario: Edit profile without password inputs
- **WHEN** an administrator opens an existing user for editing
- **THEN** the profile form does not display password or password-confirmation inputs

#### Scenario: Create user with password inputs
- **WHEN** an administrator opens the create-user page
- **THEN** the form displays required password and password-confirmation inputs

### Requirement: Validated password-change modal
The password-change action SHALL open a modal with required password and password-confirmation inputs. The new password MUST satisfy the configured password policy and the confirmation MUST match it.

#### Scenario: Successful password change
- **WHEN** an administrator submits matching credentials that satisfy the password policy
- **THEN** the selected user's password is updated and a success notification is shown

#### Scenario: Invalid password change
- **WHEN** an administrator submits a password that fails the policy or a confirmation that does not match
- **THEN** validation is displayed and the selected user's password remains unchanged

#### Scenario: Cancelled password change
- **WHEN** an administrator closes the password-change modal without submitting it
- **THEN** the selected user's password remains unchanged
