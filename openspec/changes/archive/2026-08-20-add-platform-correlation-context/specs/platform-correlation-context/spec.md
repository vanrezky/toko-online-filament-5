## Purpose

Provides a single `correlation_id` that spans incoming HTTP requests, application logs, queue jobs, external API calls, webhooks, and console executions so an entire execution can be traced across the application.

## ADDED Requirements

### Requirement: Assign a correlation ID to incoming HTTP requests
The system SHALL assign a correlation ID to every incoming HTTP request. It SHALL accept a caller-provided `X-Request-ID` or `X-Correlation-ID` header when the value is well-formed and at most 36 characters, and SHALL otherwise generate a new UUID. The assigned correlation ID SHALL be added to the outgoing response as both `X-Request-ID` and `X-Correlation-ID`.

#### Scenario: Request without an ID receives a generated ID
- **WHEN** an incoming HTTP request carries no `X-Request-ID` or `X-Correlation-ID` header
- **THEN** the system SHALL generate a new correlation ID, SHALL associate it with the request, and SHALL echo it on the response headers

#### Scenario: Valid incoming ID is preserved
- **WHEN** an incoming HTTP request carries a valid `X-Request-ID` or `X-Correlation-ID` header
- **THEN** the system SHALL use that value as the correlation ID and SHALL echo it on the response headers

#### Scenario: Malformed or oversized incoming ID is rejected
- **WHEN** an incoming HTTP request carries an `X-Request-ID` or `X-Correlation-ID` header that is longer than 36 characters or contains disallowed characters
- **THEN** the system SHALL ignore the header value and SHALL generate a new correlation ID instead

### Requirement: Propagate correlation ID into logs and queued jobs
The system SHALL make the active correlation ID automatically available to every application log line written during the execution, and SHALL carry it into every queued job dispatched during the execution without requiring call sites to pass it explicitly.

#### Scenario: Log lines include the correlation ID
- **WHEN** an application log message is written during a request or job execution
- **THEN** the log entry SHALL include the active correlation ID as structured context

#### Scenario: Dispatched job inherits the caller's correlation ID
- **WHEN** a queued job is dispatched while a correlation ID is active
- **THEN** the job payload SHALL carry that correlation ID and the worker SHALL restore it before the job executes

### Requirement: Keep job correlation IDs isolated per execution
The system SHALL restore and clear correlation context around each queued job so a long-running worker never leaks one job's correlation ID into the next job. The correlation ID SHALL be distinct from the job ID and the attempt number, and SHALL remain stable across retries of the same job execution.

#### Scenario: Consecutive jobs do not leak context
- **WHEN** a worker finishes job A and begins job B
- **THEN** the correlation ID restored for job B SHALL come only from job B's own payload, never from job A

#### Scenario: Retries keep the original correlation ID
- **WHEN** a job execution is retried
- **THEN** every attempt SHALL restore the same correlation ID captured when the job was originally dispatched, while the job ID and attempt counter remain distinct

### Requirement: Assign a correlation ID to console executions
The system SHALL assign an execution-level correlation ID when a command runs without an incoming HTTP request, so all logs and jobs from one scheduled or manual run share the same identifier.

#### Scenario: Scheduled command receives an execution ID
- **WHEN** a scheduled or manually invoked Artisan command runs without an incoming HTTP request
- **THEN** the system SHALL generate a correlation ID for the execution and SHALL include it in logs and any jobs the command dispatches