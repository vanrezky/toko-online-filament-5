## ADDED Requirements

### Requirement: Show and search integration logs by correlation ID
The Integration Logs resource SHALL display the correlation ID as a table column and SHALL support searching integration logs by correlation ID.

#### Scenario: Correlation ID is visible in the list
- **WHEN** an authorized user opens the Integration Logs list
- **THEN** each row SHALL display its correlation ID

#### Scenario: Correlation ID is searchable
- **WHEN** an authorized user searches the Integration Logs list by a correlation ID value
- **THEN** only integration logs whose correlation ID matches the search SHALL be shown