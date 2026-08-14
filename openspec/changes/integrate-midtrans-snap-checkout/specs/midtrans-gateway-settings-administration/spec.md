## ADDED Requirements

### Requirement: Authorized administrators can manage Midtrans credentials in Settings
The system SHALL register the Midtrans payment-gateway configuration page in the Filament Settings cluster. The page SHALL be visible and accessible only to a user with its assigned Shield page permission or an authenticated user whose `is_super_user` flag is true. Users without either authorization MUST not see the navigation entry and MUST be denied direct access.

#### Scenario: Role-authorized administrator opens Midtrans settings
- **WHEN** an administrator has the Midtrans gateway page permission through a role
- **THEN** the Midtrans credential page appears under Settings and the administrator can open it

#### Scenario: Flagged super user opens Midtrans settings
- **WHEN** an authenticated administrator has `is_super_user = true`
- **THEN** the Midtrans credential page appears under Settings and the administrator can open it without a role permission

#### Scenario: Unauthorized administrator requests the page
- **WHEN** an administrator has neither the Midtrans page permission nor `is_super_user = true`
- **THEN** the page is absent from navigation and direct access is denied
