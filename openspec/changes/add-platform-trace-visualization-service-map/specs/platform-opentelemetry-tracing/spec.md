## ADDED Requirements

### Requirement: Persist spans to the application database
When tracing is enabled and persistence is enabled, the system SHALL write each ended span to the application database in addition to any configured exporter. The stored span SHALL include the trace ID, span ID, parent span ID, operation name, span kind, status, start and end timestamps, duration, and allow-listed attributes and events. The system SHALL store only attributes from the strict allow-list and SHALL NOT store passwords, API keys, Authorization or cookie headers, credit card data, payment secrets, raw tokens, request or response bodies, raw SQL, or bound parameter values. Spans that end without a recorded end time SHALL be stored with an absent end timestamp so incomplete traces can be labeled honestly.

#### Scenario: Ended span is persisted
- **WHEN** tracing and persistence are enabled and a span ends
- **THEN** the system SHALL write the span to the application database with its trace ID, span ID, parent span ID, operation, kind, status, start and end timestamps, duration, and allow-listed attributes and events

#### Scenario: Persisted attributes are allow-listed only
- **WHEN** a span is persisted that has sensitive data present during execution
- **THEN** the stored attributes SHALL NOT contain any value from the restricted set

#### Scenario: Persistence is disabled
- **WHEN** tracing is enabled but persistence is disabled
- **THEN** the system SHALL NOT write any span to the application database

#### Scenario: Persistence failure does not break the request
- **WHEN** persistence is enabled and writing a span to the database fails
- **THEN** the business request or job SHALL complete normally and SHALL NOT surface the persistence failure to the caller

#### Scenario: Incomplete span records no end time
- **WHEN** a span ends without an end timestamp
- **THEN** the stored span SHALL have an absent end timestamp so the trace can be labeled partial