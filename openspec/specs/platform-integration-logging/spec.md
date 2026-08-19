## Purpose

Persist the active execution correlation ID on integration logs by default so calls to external services can be traced from the originating execution through the logged exchange, while still honoring explicit caller-provided values.

## Requirements

### Requirement: Integration logs inherit the active correlation ID
The system SHALL persist the active execution correlation ID on integration logs by default. Callers SHALL NOT be required to pass a correlation ID explicitly; an explicit caller-provided value SHALL still take precedence.

#### Scenario: Log created under an active correlation context
- **WHEN** an integration log is created while a correlation ID is active in the execution context
- **THEN** the persisted log SHALL use that correlation ID

#### Scenario: Explicit correlation ID overrides context
- **WHEN** an integration log is created with an explicit correlation ID
- **THEN** the persisted log SHALL use the explicit value

#### Scenario: No active context falls back to a generated ID
- **WHEN** an integration log is created with no explicit correlation ID and no active context
- **THEN** the system SHALL generate a new correlation ID and persist it