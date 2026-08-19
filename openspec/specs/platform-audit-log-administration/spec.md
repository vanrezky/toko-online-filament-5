## Purpose

Let authorized administrators inspect and search the audit log trail by correlation ID so a single execution can be followed across the recorded activities.

## Requirements

### Requirement: Display and filter audit logs by correlation ID
The Audit Logs resource SHALL display the correlation ID in the activity detail and SHALL support searching or filtering audit logs by correlation ID.

#### Scenario: Correlation ID is shown on the detail view
- **WHEN** an authorized user opens an audit log detail view that has a recorded correlation ID
- **THEN** the correlation ID SHALL be displayed in the metadata section

#### Scenario: Audit logs are filtered by correlation ID
- **WHEN** an authorized user searches or filters the Audit Logs list by a correlation ID value
- **THEN** only audit logs whose metadata matches the correlation ID SHALL be shown