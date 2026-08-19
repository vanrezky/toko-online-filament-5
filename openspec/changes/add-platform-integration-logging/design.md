## Context

The application is a Laravel 11 modular monolith with an MVC codebase, Filament 5 administration, Horizon, Spatie Health, and Platform modules for Queue, Health, and Audit. Platform access follows a super-user-or-Shield-permission convention. Existing provider boundaries differ: courier shipping calls Api.co.id through Laravel's HTTP client, while Midtrans payment creation and status checks use the installed SDK and payment notifications enter through the synchronous `PaymentWebhookController`.

The change must add troubleshooting evidence at integration boundaries only. It must not turn general application request logging, queue monitoring, or audit history into integration logging. Secrets must never be persisted, even for technical administrators.

## Goals / Non-Goals

**Goals:**

- Persist sanitized, bounded, correlated records of external inbound and outbound activity.
- Preserve existing business return values, exceptions, validation, transactions, and Laravel/Horizon retry ownership.
- Instrument the existing Api.co.id shipping request and Midtrans payment webhook incrementally.
- Make persisted logs safely searchable and read-only to authorized Platform users.
- Prune old records by configuration through the existing Laravel scheduler pattern.

**Non-Goals:**

- Logging every incoming application request or every outbound HTTP request.
- Replacing the Midtrans SDK, payment gateway abstraction, courier provider, Horizon, Health, or Audit Log.
- Building a tracing system, retry engine, API gateway, raw-payload viewer, or new roles.
- Backfilling historic integration events.

## Decisions

### Platform-local persistence model

Create `App\\Modules\\Platform\\Integration` with an Eloquent `IntegrationLog` model, `IntegrationLogService`, sanitizer, correlation context, and optional small value objects/enums only where they improve clarity. The model has a nullable morph-to `subject`, JSON request/response columns, explicit execution timestamps, `duration_ms`, and nullable `attempt`, `job_name`, and `queue` metadata when a caller can supply them cheaply.

The `integration_logs` table uses a normal numeric primary key, Laravel timestamps, JSON columns consistent with the MySQL schema, and indexes on `created_at`, `provider + status`, `direction`, `correlation_id`, and `subject_type + subject_id`. A `payload_truncated` boolean applies to any body truncated during persistence.

Alternative: reuse Spatie Activitylog. Rejected because its purpose is user/audit history, it lacks request/response lifecycle fields, and adding one activity for every provider execution would create audit noise.

### Best-effort, two-phase execution logging

The service creates a pending log before an instrumented execution and completes it as `success` or `failed` after the response or exception. Persistence failures are caught at the logging boundary, produce a safe Laravel warning without body or headers, and do not change the provider call's return value or rethrow behavior. Provider exceptions are recorded and then rethrown by the wrapper; callers that already catch exceptions retain their existing behavior.

Api.co.id will use a focused Laravel HTTP Client wrapper/service method that measures the request, stores sanitized request and response data, records failed HTTP responses as failed logs, and returns the same decoded JSON response shape as today. It does not register global HTTP events or macros that would capture unrelated requests.

Midtrans remains on its installed SDK. The inbound webhook controller begins a log before calling the existing gateway service, assigns a transaction subject when it can safely resolve the existing `order_id`, and completes the same record with the controller response status/result. It does not claim final queue completion because the current webhook processing is synchronous. Midtrans SDK outbound calls are not globally intercepted in this increment; adding SDK-level transport hooks would be a separate targeted change.

Alternative: global `Http` macro/event instrumentation. Rejected because it risks logging unrelated application traffic and cannot capture the Midtrans SDK.

### Sanitization and bounded bodies before persistence

`IntegrationLogSanitizer` recursively processes arrays and JSON-decodable values, case-insensitively masking Authorization, Proxy-Authorization, Cookie, Set-Cookie, api_key/apikey/api-key, access_token, refresh_token, token, password, secret, client_secret, private_key, and signature_secret. Keys are retained with the replacement `********`; no raw secret reaches `integration_logs`.

Bodies are encoded with a configurable default maximum of 64 KiB. The sanitizer keeps structured JSON if it fits, otherwise stores a safe truncated representation and marks `payload_truncated`. Response body logging is omitted for binary, image, PDF, ZIP, download, and other non-JSON/non-text content types; response metadata and headers remain available.

Alternative: sanitize only on display or store encrypted raw data. Rejected because display-only masking leaves a database secret exposure and encrypted raw data violates the explicit no-raw-payload scope.

### Correlation and subjects

`IntegrationCorrelationContext` lazily generates a UUID correlation ID per active PHP execution unless a caller provides one. The context lets a business operation reuse an ID across relevant provider calls without deriving it from a database key. The inbound webhook receives a new ID and uses the existing payload order ID solely for optional `Transaction` subject resolution. The implementation does not add a global request-ID middleware because that would expand into request tracing.

### Filament and authorization

Add a read-only `IntegrationLogResource` to navigation group `Platform`, after Audit Logs. It uses the same authorization convention as the existing Platform surfaces: super user or `View:IntegrationLogs`. The resource provides direction/provider/status badges, HTTP status and duration, filters for direction, provider, status, HTTP status class/range, date range, and has-error; its indexed search is limited to endpoint, correlation ID, subject ID, and error message. Detail sections display only persisted sanitized/pretty-printed data.

Alternative: a custom Filament Page. Rejected because a Resource matches existing Audit Logs table/detail behavior and reduces presentation-layer code.

### Retention

Add `config/integration-logging.php` with `INTEGRATION_LOG_RETENTION_DAYS` defaulting to 90. A dedicated `integration-logs:prune` command is scheduled daily with `onOneServer()` and `withoutOverlapping()`. The command deletes only logs older than configured retention, never from request lifecycle.

## Risks / Trade-offs

- [High-volume shipping quote calls increase table growth] → Index common filters, cap payloads, and prune daily.
- [Sanitizer misses a provider-specific secret key] → Centralize the case-insensitive list, test nested values, and allow configuration to extend it without UI changes.
- [Logging persistence outage hides troubleshooting evidence] → Preserve business behavior and emit minimal, secret-free Laravel warnings.
- [Webhook failures need a persisted record even when controller returns 500] → Create pending before processing and complete it in `try/catch/finally` paths.
- [Midtrans SDK lacks an existing transport response hook] → Keep its current implementation intact; scope this increment to inbound Midtrans evidence and outbound Laravel HTTP Client courier evidence.

## Migration Plan

1. Deploy code and migration together; the new table is additive and has no backfill.
2. Seed or provision `View:IntegrationLogs` by the established Shield process before assigning it to non-super users.
3. Ensure the existing scheduler runs so daily pruning becomes active.
4. Rollback application code safely by disabling instrumentation; rolling back the migration is optional and only safe after confirming no audit need for newly collected records.

## Open Questions

- None. The current Midtrans webhook is synchronous, so the initial lifecycle maps directly to pending, success, and failed without queue-state tracking.
