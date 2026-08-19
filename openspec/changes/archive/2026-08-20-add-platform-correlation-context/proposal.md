## Why

A single execution currently cannot be traced across the application: logs, queue jobs, external API calls, and audit entries have no shared identifier, and the existing request-scoped `IntegrationCorrelationContext` never reaches Horizon workers. This change lays the observability foundation by propagating one `correlation_id` transparently across HTTP requests, queue jobs, integrations, audits, and logs using Laravel's built-in `Context` capability.

## What Changes

- Add a Platform correlation context foundation backed by Laravel `Context`, with a small `Correlation` helper for reading, setting, and propagating `correlation_id`.
- Add an HTTP middleware that reads `X-Request-ID` / `X-Correlation-ID`, validates and bounds incoming identifiers, generates a UUID when absent or invalid, stores the value in context, and echoes it on `X-Request-ID` and `X-Correlation-ID` response headers.
- Register a console execution hook so scheduled commands and CLI runs without an incoming HTTP request receive an execution-level `correlation_id`.
- Provide an outbound HTTP client macro that attaches the active correlation ID as `X-Correlation-ID`, and apply it to the instrumented courier integration.
- Rewire the existing `IntegrationCorrelationContext` to delegate to Laravel `Context` so integration logs automatically inherit the active `correlation_id` without duplicate mechanisms.
- Add `correlation_id` to audit request metadata so activity logs record it via the existing properties/context mechanism.
- Surface `correlation_id` in the Platform Filament tools: Audit Logs detail and search/filter, Integration Logs table column (search already present), and Queue Monitor recent-failed-jobs detail.
- Add feature/unit tests covering HTTP, queue propagation and cleanup, integration auto-fill, webhook, and audit association.

## Capabilities

### New Capabilities

- `platform-correlation-context`: Provides a transparent `correlation_id` that spans HTTP requests, application logs, queue jobs, external API calls, webhooks, console executions, and audit metadata.

### Modified Capabilities

- `platform-integration-logging`: Correlation IDs now come from the active execution context by default instead of an isolated request-scoped generator.
- `platform-integration-log-visibility`: The Integration Logs resource shows `correlation_id` as a table column alongside the existing search.
- `platform-audit-logging`: Audit activity captures the active `correlation_id` in its context metadata.
- `platform-audit-log-administration`: The Audit Logs resource displays `correlation_id` and adds search/filter support for it.
- `platform-queue-monitoring`: The Queue Monitor page surfaces `correlation_id` on recent failed jobs when the stored payload carries it.

## Impact

- New middleware `App\Http\Middleware\CorrelationIdMiddleware`, new helper `App\Modules\Platform\Support\Correlation`, and registration updates in `App\Http\Kernel` and `App\Providers\AppServiceProvider`.
- Modified `App\Modules\Platform\Integration\Support\IntegrationCorrelationContext` (delegates to Laravel `Context`), `App\Modules\Platform\Audit\Services\AuditLogService` (correlation metadata), `App\Services\ApicoidOngkirService` (outbound header), and the Filament Audit Logs, Integration Logs, and Queue Monitor surfaces.
- No database migration is required: `integration_logs.correlation_id` already exists, and audit activity uses its existing JSON properties.
- Existing `dispatch(new SomeJob(...))` call sites and integration call sites remain unchanged (backward compatible).
- No OpenTelemetry, Grafana, Prometheus, Loki, or Tempo components are installed.