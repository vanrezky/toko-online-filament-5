# Design: OpenTelemetry Tracing

## Context

See proposal.md - Why. The application is a Laravel 11 / PHP 8.2+ monolith with a Filament admin panel, Horizon queue workers, existing correlation-ID plumbing (`App\Modules\Platform\Support\Correlation` via Laravel Context), Integration Logs, and Audit Logs. Phase 3 must add real application tracing that is linkable to the existing correlation ID.

Constraints from the specs:

- HTTP root spans, external HTTP client child spans, DB query child spans, and queue job spans.
- Correlation ID on root/job spans.
- Configurable sampling and a full disable switch.
- Strict allow-list so sensitive data is never exported.
- Exporter failure must never break the business request.
- No new schema.

## Goals / Non-Goals

**Goals**

- A self-contained tracing module under `app/Modules/Platform/Tracing` that builds a `TracerProvider`, instruments HTTP, HTTP client, database queries, and queue jobs, and exports via OTLP.
- Full disable/opt-in so the feature is off by default and safe to ship.
- Testable in the existing PHPUnit/Sail environment without a real collector.

**Non-Goals**

- A trace viewer / service map UI (Phase 4).
- Metrics or logs exporters.
- Span processor persistence to the application database (traces are exported, not stored).
- Instrumenting every third-party package (e.g., Midtrans SDK internals beyond what the HTTP client sees).

## Decisions

### D1: Manual OpenTelemetry SDK instrumentation — not the auto-instrumentation package

**Decision:** Use `open-telemetry/sdk`, `open-telemetry/api`, and `open-telemetry/exporter-otlp` with manual instrumentation (middleware + listeners). Do **not** use `open-telemetry/opentelemetry-auto-laravel`.

**Rationale:** The auto-instrumentation package requires the `ext-opentelemetry` PHP C extension installed via PECL. That extension is not present in the Sail runtime (`vendor/laravel/sail/runtimes/8.3`), would require a custom Dockerfile build, and would have to be added to the CI deployment workflow. Manual SDK instrumentation needs no C extension, works in the existing Sail container and CI unchanged, and gives exact control over which attributes are recorded — which the sensitive-data requirement demands.

**Alternatives considered:**

- `open-telemetry/opentelemetry-auto-laravel`: rejected — requires PECL extension and Dockerfile/CI changes; broad auto-instrumentation risks capturing sensitive data.
- Custom SDK build from scratch: rejected — the official SDK packages are the maintained path.

### D2: A dedicated tracing config file with environment-driven defaults

**Decision:** Add `config/tracing.php` exposing `enabled`, `service_name`, `service_version`, `environment`, `sampler_ratio`, `exporter_endpoint`, `protocol`, and per-source toggles (http, http_client, database, queue), each defaulting from `OTEL_*`-style env vars. Tracing is **disabled by default** (`tracing.enabled=false`).

**Rationale:** The spec requires configurable sampling and a full disable switch. A single config file keeps all knobs discoverable and testable, and defaulting off guarantees the feature ships without changing behavior.

### D3: In-memory exporter for tests

**Decision:** When `tracing.exporter` is `memory` (or in the `testing` environment), register an in-memory `InMemorySpanExporter` so tests can assert on exported spans without a collector.

**Rationale:** The SDK ships an in-memory exporter. This makes the acceptance scenarios directly testable in PHPUnit, including sampling, disable, sensitive-data, and exporter-failure behavior.

### D4: Best-effort tracing with a guarded facade boundary

**Decision:** All trace calls go through the module so that any SDK/extension/network error is caught and logged (best-effort) without propagating to application code. The middleware wraps span start/end in try/finally and never throws.

**Rationale:** The spec requires exporter failures to never break the request. Centralizing behind a service plus try/finally in middleware and listeners makes this a single, testable guarantee rather than a per-callsite concern.

### D5: Attribute allow-list for sensitive-data protection

**Decision:** Maintain an explicit allow-list of span attributes the module is permitted to record. Never forward raw headers, bodies, SQL, or bound values. For DB spans, derive `db.system`, `db.operation` (query type), `db.namespace` (table), and `db.duration_ms` only. For HTTP client spans, record method, URL, status, and duration only.

**Rationale:** The spec forbids sensitive data absolutely. An allow-list enforced in one place is simpler to audit than denylists scattered across instrumentations.

### D6: Queue instrumentation via queue lifecycle events

**Decision:** Instrument queue jobs with a `TracingJobMiddleware` class hooked into the global `JobProcessing` / `JobProcessed` / `JobFailed` events. A span starts when a correlation ID is active for the job execution and ends on processed or failed; no span is produced when no correlation ID is active. No dispatch-time parent/child linking is attempted; the span is a standalone job span carrying the correlation ID. An internal stack keeps spans balanced when jobs are nested (e.g., a sync job dispatching another sync job).

**Rationale:** The spec requires a job span when a correlation ID is active and none otherwise. Queue lifecycle events are registered globally in the service provider, so every job is covered without modifying individual job classes, and they integrate with the existing correlation context restoration used by the queue worker.

## Risks / Trade-offs

- [OTLP exporter dependency availability] → exporter is only constructed when enabled; `tracing.enabled` defaults false, and `InMemorySpanExporter` is used in tests.
- [Batch span processor buffering/export timing in tests] → use `SimpleSpanProcessor` in tests (synchronous) and `BatchSpanProcessor` in production so assertions are deterministic.
- [HTTP client instrumentation misses SDK-internal calls (e.g., Midtrans uses its own Guzzle)] → instrument at the Guzzle middleware level (`GuzzleHttp\Middleware::tap` / a custom handler) so all client traffic is covered regardless of which wrapper is used; HTTP spans are scoped to the application HTTP client.
- [DB query parsing for table names] → parse the SQL prefix heuristically (insert/update/select/delete + first table token) and guard with try/catch; on parse failure, record the operation without a table name rather than skipping the span or logging SQL.
- [Overhead from always-on query listener] → listener only records when tracing is enabled and a span context is active; disabled by default.
- [Sampling ratio > 0 but < 1 nondeterminism in tests] → tests that assert spans use ratio 1.0; a separate test asserts the sampler is configured (not that specific spans are dropped).

## Migration Plan

1. Add composer dependencies (dev + runtime).
2. Add `config/tracing.php` (defaults off).
3. Add module classes and register middleware/listeners in `AppServiceProvider` (or a dedicated `TracingServiceProvider`).
4. Ship with `tracing.enabled=false`; verify existing behavior unchanged.
5. In a later deploy, enable tracing against a collector and verify exported traces.

**Rollback:** set `TRACING_ENABLED=false` (or `tracing.enabled=false`) — no code change, no data migration.

## Open Questions

- Which OTLP collector/backend will be used in production (Jaeger, Tempo, etc.)? Deferrable — the OTLP endpoint is config-driven and does not change the design.