## Purpose

Provides real application tracing via OpenTelemetry so platform engineers can observe the true end-to-end path and timing of a request across HTTP, external HTTP calls, database queries, and queue jobs, with each trace linkable to the existing correlation ID and protected against sensitive-data leakage.

## ADDED Requirements

### Requirement: Instrument incoming HTTP requests
The system SHALL create a root span for each incoming HTTP request when tracing is enabled. The span SHALL capture the HTTP method, route or path, response status code, and total duration. The span SHALL record any unhandled exception thrown while handling the request. The span SHALL carry the active correlation ID as an attribute when a correlation ID is present.

#### Scenario: Request produces an HTTP span
- **WHEN** tracing is enabled and an incoming HTTP request is handled
- **THEN** the system SHALL export a root span for the request with the HTTP method, route, response status code, and duration recorded

#### Scenario: Request carries a correlation ID
- **WHEN** tracing is enabled and an incoming HTTP request has an active correlation ID
- **THEN** the exported root span SHALL include the correlation ID as a span attribute

#### Scenario: Unhandled exception is recorded
- **WHEN** tracing is enabled and handling an incoming HTTP request throws an unhandled exception
- **THEN** the system SHALL record the exception on the root span

### Requirement: Instrument external HTTP client calls
The system SHALL create a child span for each outgoing HTTP client call made through the application HTTP client when tracing is enabled. The child span SHALL be linked to the root span and SHALL capture the HTTP method, URL, response status code, and duration without capturing request headers, request body, response headers, or response body.

#### Scenario: Outbound call produces a child span
- **WHEN** tracing is enabled and the application makes an outgoing HTTP request through the HTTP client
- **THEN** the system SHALL export a child span under the root span with the HTTP method, URL, response status code, and duration

#### Scenario: Outbound call payloads are not captured
- **WHEN** tracing is enabled and the application makes an outgoing HTTP request
- **THEN** the exported span SHALL NOT contain the request or response headers, the request or response body, or any bound payload values

### Requirement: Instrument database queries
The system SHALL create a child span for each database query executed while tracing is enabled. The child span SHALL capture the query type, the affected table or tables, and the query duration. The span SHALL NOT contain the raw SQL text or any bound parameter values.

#### Scenario: Query produces a child span
- **WHEN** tracing is enabled and the application executes a database query
- **THEN** the system SHALL export a child span with the query type, affected table, and duration

#### Scenario: Query text and bindings are not captured
- **WHEN** tracing is enabled and the application executes a database query
- **THEN** the exported span SHALL NOT contain the raw SQL statement or any bound parameter values

### Requirement: Instrument queued jobs
The system SHALL create a span for a queued job when tracing is enabled and a correlation ID is active for the job execution. The span SHALL capture the job class name and execution duration and SHALL carry the correlation ID as an attribute.

#### Scenario: Job with an active correlation ID produces a span
- **WHEN** tracing is enabled and a queued job runs with an active correlation ID
- **THEN** the system SHALL export a span for the job with the job class name, duration, and correlation ID attribute

#### Scenario: Job without a correlation ID produces no span
- **WHEN** tracing is enabled and a queued job runs with no active correlation ID
- **THEN** the system SHALL NOT export a span for that job

### Requirement: Link traces to correlation IDs
The system SHALL make the active correlation ID available to the trace so an exported trace can be matched to the existing correlation-tagged executions. The correlation ID SHALL be exported as a span attribute on the root span and on any job span.

#### Scenario: Trace is linkable to a correlation execution
- **WHEN** tracing is enabled and a request or job produces a trace with an active correlation ID
- **THEN** the exported trace SHALL include the correlation ID such that an external trace backend can filter or match by it

### Requirement: Provide configurable sampling
The system SHALL allow the tracing sampling behavior to be configured at runtime via configuration or environment variables. The sampling ratio SHALL be a value between 0 and 1, where 1 records every span and 0 records none.

#### Scenario: Sampling ratio is honored
- **WHEN** tracing is enabled and the configured sampling ratio is less than 1
- **THEN** the system SHALL only export spans selected by the configured sampling behavior

### Requirement: Allow tracing to be disabled
The system SHALL allow tracing to be completely disabled via configuration or an environment variable. When disabled, the system SHALL NOT create spans, SHALL NOT connect to any exporter, and SHALL NOT affect the request or job execution.

#### Scenario: Tracing disabled produces no spans
- **WHEN** tracing is disabled
- **THEN** the system SHALL export no spans and SHALL leave the request and job behavior unchanged

### Requirement: Protect sensitive data
The system SHALL only export attributes from a strict allow-list. The system SHALL NOT capture passwords, API keys, Authorization or cookie headers, credit card data, payment secrets, raw tokens, request or response bodies, raw SQL, or bound parameter values.

#### Scenario: Sensitive values are never exported
- **WHEN** tracing is enabled and a request, query, or job executes with sensitive data present
- **THEN** the exported spans SHALL NOT contain any attribute from the restricted set

### Requirement: Tolerate exporter failures
The system SHALL ensure that an exporter failure, a missing exporter endpoint, or any tracing error does not break or alter the business request or job execution. Tracing SHALL be best-effort and SHALL never raise an exception into application code.

#### Scenario: Exporter unavailable does not break the request
- **WHEN** tracing is enabled and the exporter is unreachable or fails
- **THEN** the business request or job SHALL complete normally and SHALL NOT surface the tracing failure to the caller