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

### Requirement: Authorized administrators can copy Midtrans operational endpoints
The protected Midtrans settings page SHALL display copyable, read-only values for the application's Payment Notification URL and the Finish, Unfinish, and Error Redirect URLs. The page MUST state that the application URL needs to be a publicly reachable HTTPS URL before production activation. These operational URLs MUST NOT be persisted as gateway credentials.

#### Scenario: Administrator configures the Midtrans dashboard
- **WHEN** an authorized administrator opens the Midtrans settings tab
- **THEN** the administrator can copy the current notification and redirect URLs for the Midtrans dashboard without viewing or changing sensitive credentials
