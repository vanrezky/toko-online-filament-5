## ADDED Requirements

### Requirement: Authenticated customers can change their password
The system SHALL provide an authenticated customer-facing Change Password account destination and a dedicated customer-guarded endpoint. The form SHALL require current password, new password, and password confirmation.

#### Scenario: Customer opens change password
- **WHEN** an authenticated customer selects Change Password from account navigation
- **THEN** the account page displays a dedicated password form without exposing the stored password

#### Scenario: Customer changes password successfully
- **WHEN** the customer submits their correct current password, a valid new password, and matching confirmation
- **THEN** the system updates the customer password, returns the established success feedback, and clears sensitive form fields

### Requirement: Password changes enforce existing policy and current-credential validation
The system SHALL validate a proposed password with the store's existing `securePassword(8)` policy and SHALL verify the current password against the customer guard before updating the stored credential.

#### Scenario: Current password is incorrect
- **WHEN** a customer submits an incorrect current password
- **THEN** the request is rejected with a field validation error and the stored password remains unchanged

#### Scenario: New password is invalid or unconfirmed
- **WHEN** a customer submits a password that violates the configured policy or whose confirmation does not match
- **THEN** the request is rejected with a field validation error and the stored password remains unchanged
