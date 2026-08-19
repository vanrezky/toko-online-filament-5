## ADDED Requirements

### Requirement: Persist bounded sanitized integration-boundary activity
The system SHALL persist integration logs only for explicitly instrumented inbound webhooks and outbound provider API executions. Each log SHALL contain direction, provider, type, method, endpoint or URL, execution status, start and finish timestamps, duration, correlation ID, optional polymorphic subject, and applicable HTTP response/error fields. Request and response headers and bodies SHALL be sanitized before persistence, and body capture SHALL be bounded by configured size limits.

#### Scenario: Outbound courier request succeeds
- **WHEN** the application requests shipping costs from the configured courier API
- **THEN** it SHALL create an outbound API integration log with the provider, method, endpoint, sanitized request data, response data, HTTP status, success status, and duration

#### Scenario: Outbound provider execution throws
- **WHEN** an explicitly instrumented provider execution throws a connection, timeout, or request exception
- **THEN** the system SHALL persist a failed integration log with error class, error message, and duration before preserving the original exception behavior

#### Scenario: Integration persistence fails
- **WHEN** the system cannot persist an integration log
- **THEN** it SHALL emit a secret-free application warning and SHALL NOT alter the business request or provider error behavior

### Requirement: Protect sensitive and oversized payload data
The system SHALL recursively mask sensitive header and payload values before any integration log persistence. It SHALL mask Authorization, Proxy-Authorization, Cookie, Set-Cookie, api_key, apikey, api-key, access_token, refresh_token, token, password, secret, client_secret, private_key, and signature_secret without case-sensitive key matching. It SHALL mark truncated bodies and SHALL NOT persist binary, image, PDF, ZIP, or download response bodies.

#### Scenario: Nested credential is captured
- **WHEN** an instrumented payload contains a sensitive key inside nested arrays or objects
- **THEN** the persisted value for that key SHALL be `********` and the raw secret SHALL not be present in the database

#### Scenario: Provider body exceeds configured limit
- **WHEN** an instrumented text or JSON body exceeds the configured payload limit
- **THEN** the system SHALL store only the bounded safe representation and mark the integration log as payload-truncated

### Requirement: Correlate and relate integration activity
The system SHALL assign a UUID or ULID correlation ID to every integration log and SHALL reuse a caller-provided or active execution correlation ID for related activity. It SHALL allow an integration log to have no subject and SHALL support an optional polymorphic business-model subject.

#### Scenario: Related activity shares a context
- **WHEN** multiple instrumented integration executions are performed under the same active correlation context
- **THEN** their logs SHALL persist the same correlation ID

#### Scenario: Webhook identifies a transaction
- **WHEN** a valid Midtrans webhook payload identifies an existing transaction
- **THEN** its integration log SHALL reference that transaction as its optional subject

### Requirement: Capture Midtrans webhook lifecycle
The system SHALL create an inbound webhook integration log before Midtrans webhook processing begins and SHALL complete it with success or failed status, response status, duration, and error information when applicable. It SHALL retain the current webhook signature validation, transaction behavior, and HTTP response behavior.

#### Scenario: Midtrans webhook completes successfully
- **WHEN** a valid Midtrans webhook is processed successfully
- **THEN** the corresponding inbound integration log SHALL be marked success with the returned HTTP status and processing duration

#### Scenario: Midtrans webhook processing fails
- **WHEN** Midtrans webhook processing throws an exception
- **THEN** the corresponding inbound integration log SHALL be marked failed before the controller returns its existing failure response

### Requirement: Retain logs predictably
The system SHALL provide configurable integration-log retention with a default of 90 days and SHALL schedule a non-overlapping, single-server pruning task. It SHALL not prune integration logs from web request handling.

#### Scenario: Scheduled pruning runs
- **WHEN** the daily integration-log pruning task runs
- **THEN** it SHALL remove only logs older than the configured retention period
