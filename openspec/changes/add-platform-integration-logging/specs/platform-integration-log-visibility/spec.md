## ADDED Requirements

### Requirement: Provide authorized read-only integration log visibility
The system SHALL expose a read-only Integration Logs resource under the Filament Platform navigation group. It SHALL allow access only to a super user or a user with the existing authorization-style permission `View:IntegrationLogs`, and SHALL not expose create, edit, or delete actions.

#### Scenario: Authorized technical user opens logs
- **WHEN** a super user or a user with `View:IntegrationLogs` opens the Platform navigation
- **THEN** the user SHALL be able to view the Integration Logs list and its log details

#### Scenario: Unauthorized user requests logs
- **WHEN** a user without super-user status or `View:IntegrationLogs` requests the Integration Logs resource
- **THEN** the system SHALL deny access

### Requirement: Present troubleshooting-oriented sanitized log detail
The resource SHALL list timestamp, direction, provider, method, endpoint, status, HTTP status, duration, and subject. It SHALL provide filters for direction, provider, status, HTTP status range or class, date range, and error presence; indexed search SHALL be limited to endpoint, correlation ID, subject ID, and error message. Its detail view SHALL display persisted sanitized request and response sections as pretty-printed structured values and SHALL not offer unmasked payload access.

#### Scenario: Technical user investigates a failed log
- **WHEN** an authorized technical user opens a failed integration log
- **THEN** the detail view SHALL show provider, direction, endpoint, status, duration, correlation ID, subject when present, sanitized request/response content, and error information

#### Scenario: User narrows logs for troubleshooting
- **WHEN** an authorized technical user filters by provider, direction, status, HTTP status, date range, or error presence
- **THEN** the list SHALL return only integration logs matching the selected filters
