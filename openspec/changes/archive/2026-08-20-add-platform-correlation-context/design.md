## Context

The application has no correlation/request-ID mechanism that spans HTTP, queue, integration, and audit (see proposal.md - Why). Laravel 11.53 is installed with the `Illuminate\Support\Facades\Context` capability fully available: context values are merged automatically into every log line, dehydrated into dispatched job payloads, and hydrated (and cleared) around each job's execution scope. The existing `IntegrationCorrelationContext` is a request-scoped UUID holder used only by `IntegrationLogService`; it never reaches Horizon workers. `integration_logs.correlation_id` already exists as an indexed UUID column, and audit metadata is stored as JSON in the Activitylog `properties` column.

## Goals / Non-Goals

**Goals:**
- One `correlation_id` value per execution that is available without passing parameters through method signatures.
- Automatic inclusion of the active `correlation_id` in every log line, queued job, integration log, and audit entry.
- Safe handling of malformed or oversized inbound identifiers.
- Zero code changes at existing `dispatch(...)` and integration-log call sites.

**Non-Goals:**
- No OpenTelemetry/Grafana/Prometheus/Loki/Tempo, no tracing UI, no observability dashboards.
- No new database migration (existing columns and JSON properties are reused).
- No change to the queue driver, Horizon configuration, or job middleware conventions.

## Decisions

### 1. Use Laravel `Context` as the correlation store
Store `correlation_id` with `Context::add()` instead of extending the isolated `IntegrationCorrelationContext` singleton. Rationale: it is the built-in Laravel capability for this exact purpose (in-memory, request-scoped, auto-appended to logs, auto-propagated to jobs, auto-cleared between jobs). Alternatives considered: a custom static registry (would duplicate framework behavior and reintroduce worker leakage risk); `Log::withContext()` (log-only, does not propagate to jobs); a service passed through constructors (violates the no-plumbing requirement). Keep the existing `IntegrationCorrelationContext` class as a thin delegator so `IntegrationLogService` and its tests keep working unchanged.

### 2. Single `correlation_id` terminology
The project already uses `correlation_id` as the persisted identifier on integration logs; no separate `request_id` convention exists. Per the requirement to pick the simplest consistent design, one identifier (`correlation_id`) serves as both the request identifier and the propagated identifier. The HTTP middleware accepts either `X-Request-ID` or `X-Correlation-ID` and echoes the same value on both response headers.

### 3. Generate UUIDs to match the existing schema
`integration_logs.correlation_id` is a `uuid` column (`char(36)`), and existing code generates `Str::uuid()`. Generate UUIDs for new IDs and reject inbound IDs longer than 36 characters or outside a safe charset (`[A-Za-z0-9._:-]`), so any accepted value always fits the column.

### 4. Global HTTP middleware
Register `CorrelationIdMiddleware` in the kernel's global `$middleware` so web, API, webhook, and Filament requests all receive correlation handling without per-route wiring. The middleware runs early (before sessions/auth) to keep logs minimal and avoid leaking prior execution context; it stores the ID in `Context` and appends response headers.

### 5. Queue propagation is free
Laravel's `ContextServiceProvider` already registers `Queue::createPayloadUsing(...)` (dehydration) and a `JobProcessing` listener (hydration), and the context repository is scoped per job execution. No custom queue middleware is added; the built-in mechanism satisfies dispatch → serialize → worker → restore → clear. Retries rehydrate from the same payload context, keeping the original correlation ID.

### 6. Console/scheduler execution ID
Listen to `Console\Events\CommandStarting` in `AppServiceProvider` and set `correlation_id` when absent. Skip queue-worker commands (`queue:work`, `horizon`, `queue:listen`, `horizon:supervisor`) so a long-running worker process does not anchor a command-level ID in its base scope. Jobs dispatched from commands then dehydrate this execution ID automatically.

### 7. Outbound HTTP via a `PendingRequest` macro
Expose `Http::withCorrelation()` by registering a macro on `Illuminate\Http\Client\PendingRequest` that adds `X-Correlation-ID` from context. Apply it only where the provider contract tolerates extra headers (the courier integration). Provider SDK calls (Midtrans SDK) are left untouched so their strict signature contracts are not altered.

### 8. Audit correlation via existing metadata path
Extend `AuditLogService::requestMetadata()` to include `correlation_id` from context. Both the `HasPlatformAuditMetadata` trait (automatic model activity) and `logBusinessAction()` (explicit business activity) already funnel through this method, so one change covers both without touching the Activitylog package.

### 9. Filament surfaces reuse existing resources
Audit Logs: add `correlation_id` entries to the metadata section and a search/filter against the JSON `properties->context->correlation_id`. Integration Logs: add a correlation ID table column (search filter already exists). Queue Monitor: read recent failed jobs from Horizon's `JobRepository`, extract `correlation_id` from the stored payload's `illuminate:log:context`, and render it in the Blade page. Horizon itself is not modified.

## Risks / Trade-offs

- **Accepted inbound IDs widen the stored value set** (not strictly UUIDs) → bounded to 36 chars with a safe charset; the `integration_logs.correlation_id` column already fits.
- **Console command listener covers every command** → excluded queue-worker commands; the listener only acts when no correlation ID is present, and job scopes isolate job context regardless.
- **Outbound header only on the courier integration** → the macro centralizes the capability so future integrations opt in with one line; provider SDKs remain untouched.
- **Queue Monitor depends on payload contents** → jobs carry context only when dispatched under an active correlation ID; the UI degrades gracefully to an empty value otherwise.

## Migration Plan

- Deploy as a normal branch/PR; no schema migration and no new dependencies.
- Rollback: revert the branch; the middleware and provider wiring are additive and do not alter existing persistence behavior. Existing integration logs created before this change retain their previously generated IDs.

## Open Questions

None that affect the specs or task breakdown.