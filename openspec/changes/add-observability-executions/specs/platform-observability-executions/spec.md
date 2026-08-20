## Purpose

Lets platform engineers inspect a complete execution timeline for any correlation ID, grouping actual integration, audit, and queue events from existing logs without fabricating data.

## ADDED Requirements

### Requirement: Executions list with correlation search

The system SHALL provide an Executions page under the Observability cluster listing executions derived from existing correlation-tagged events. Executions SHALL be searchable by correlation ID.

#### Scenario: Search by correlation ID

- **WHEN** an operator searches for an existing correlation ID
- **THEN** the system returns the execution matching that correlation ID

#### Scenario: Search with no matches

- **WHEN** an operator searches for a correlation ID with no recorded events
- **THEN** the system shows an empty state without error

#### Scenario: Same correlation groups events

- **WHEN** multiple events share the same correlation ID
- **THEN** the system groups them into a single execution

#### Scenario: Different correlations remain separate

- **WHEN** two executions have distinct correlation IDs
- **THEN** the system lists them as separate executions

### Requirement: Execution detail timeline

The system SHALL provide an execution detail page rendering a timeline of actual events ordered by their recorded start timestamps. The timeline SHALL include integration log events when a correlation matches, audit log events when the correlation is present in their properties, and queue failed-job events when the correlation is present in the job payload.

#### Scenario: Timeline ordering

- **WHEN** an operator opens an execution detail page
- **THEN** the timeline SHALL order events by recorded start time ascending

#### Scenario: Integration logs linked

- **WHEN** an integration log exists with the execution's correlation ID
- **THEN** the timeline SHALL show that integration log event with a link to the Integration Log record

#### Scenario: Audit logs linked

- **WHEN** an audit log's properties contain the execution's correlation ID
- **THEN** the timeline SHALL show that audit event with a link to the Audit Log record

#### Scenario: Queue events linked

- **WHEN** a queue failed job's payload contains the execution's correlation ID
- **THEN** the timeline SHALL show that queue event

#### Scenario: Empty execution

- **WHEN** an execution has no correlated events
- **THEN** the detail page SHALL show an empty state indicating no recorded events

### Requirement: Missing or failed data handled without fabrication

The system SHALL handle events with missing duration or missing status without inventing values. Failed events SHALL be visually distinguishable.

#### Scenario: Missing duration

- **WHEN** an event has no recorded duration
- **THEN** the system SHALL display the event without a duration value rather than computing a fabricated one

#### Scenario: Missing status

- **WHEN** an event has no recorded status
- **THEN** the system SHALL display the event without a status badge rather than inventing one

#### Scenario: Failed event distinction

- **WHEN** an event has a failed status
- **THEN** the system SHALL render it with a distinct visual treatment

### Requirement: Safe and bounded data access

The system SHALL load only the events needed for the requested execution and SHALL query using existing indexes on correlation identifiers, ordering by timestamp. The system SHALL NOT load entire log tables and SHALL sanitize sensitive event data.

#### Scenario: Bounded query by correlation

- **WHEN** the system loads events for an execution
- **THEN** it SHALL filter by the correlation identifier using indexed lookups rather than scanning full log tables

#### Scenario: Sensitive data sanitized

- **WHEN** the timeline renders event details
- **THEN** API keys, authorization headers, secrets, credentials, and raw payment tokens SHALL NOT be exposed

#### Scenario: No duplicate correlation mechanism

- **WHEN** the system groups events
- **THEN** it SHALL reuse the existing correlation context mechanism and SHALL NOT introduce a parallel one