## ADDED Requirements

### Requirement: Audit activity records the active correlation ID
The system SHALL capture the active execution correlation ID into audit activity metadata whenever a correlation ID is available, using the existing properties/context mechanism without changing the audit event or actor semantics.

#### Scenario: Activity logged during a request with an active correlation ID
- **WHEN** audit activity is recorded while a correlation ID is active
- **THEN** the activity's metadata SHALL include the correlation ID

#### Scenario: Activity logged without an active correlation ID
- **WHEN** audit activity is recorded while no correlation ID is active
- **THEN** the activity SHALL be recorded normally without a correlation ID field