## Why

OpenTelemetry tracing (Issue #61) already captures real spans for HTTP, external HTTP calls, database queries, and queue jobs, but it only exports them over OTLP to an external collector. In the default deployment there is no collector, so trace data is ephemeral: platform engineers cannot inspect, search, or aggregate the traces the application actually produces. Issue #62 asks for an in-admin observability UI — trace list, trace detail with a truthful waterfall and span tree, and a read-only service map with backend-aggregated metrics. That requires trace data to be persisted and queryable in the application database, then surfaced through the existing Filament admin panel.

## What Changes

- **Persist spans to the application database** via a new `tracing_spans` table and a DB span processor chained alongside the existing exporter. Only allow-listed attributes (reusing `TracingAttributes`) are stored; sensitive data stays protected. Persistence is gated by a new `tracing.persist` config flag so current OTLP-only behavior remains the default.
- **New `View:Traces` permission** (with `View:ServiceMap`) so the new pages can be gated independently, seeded by `ShieldSeeder`.
- **Trace list page** under `Platform → Observability → Traces`: columns for timestamp, trace ID, correlation ID, root operation, status, duration, span count, and error count, with filters for time range, status, operation, duration, error, and correlation ID, plus search by trace ID or correlation ID. Results are paginated; large trace sets are never loaded at once.
- **Trace detail page** under `Traces/{trace}`: header with root operation, trace ID, correlation ID, duration, status, span count, and error count; a **waterfall rendered from actual span start/end timestamps** (no fabricated spans); a nested span tree/hierarchy; and per-span detail (trace ID, span ID, parent span ID, operation, start/end, duration, status, sanitized attributes, and sanitized events).
- **Slow span highlighting** using a new configured threshold (`tracing.slow_span_threshold_ms`); failed spans are visually distinguishable.
- **Integration Log drill-down**: spans carrying a correlation ID link to the related Integration Logs; actions to copy the correlation ID and view related Audit Logs where supported.
- **Read-only service map** derived from actual stored trace relationships (no hardcoded nodes/links), with metrics (requests, error rate, avg, P95) aggregated at DB level and shown only when calculable, and time-range presets of last 1h/24h/7d.
- **Partial/incomplete traces are labeled honestly** ("Partial trace").
- **Frontend approach**: pure Blade/CSS in the existing Filament panel. No graph library is added; the waterfall is a horizontal CSS/SVG rendering computed server-side, and the service map is a Blade-rendered diagram/table. This keeps the admin shell 100% Blade and avoids new JS bundle inputs and dependencies.

## Capabilities

### New Capabilities
- `platform-trace-visualization`: list, filter, search, detail, waterfall, span tree, span detail, slow-span and failed-span highlighting, and Integration Log/Audit Log drill-down for persisted traces in the Filament admin panel.
- `platform-trace-service-map`: read-only service map and DB-aggregated metrics (requests, error rate, avg, P95) over configurable time ranges.

### Modified Capabilities
- `platform-opentelemetry-tracing`: the tracing pipeline gains an optional DB persistence stage — when `tracing.persist` is enabled, ended spans are also written to the application database (allow-listed attributes only), and incomplete spans are recorded so partial traces can be labeled honestly. Existing OTLP export, sampling, disable, and sensitive-data guarantees are unchanged.

## Impact

- **Schema**: new `tracing_spans` table (migration `2026_XX_XX_XXXXXX_create_tracing_spans_table.php`), plus `database/schema/mysql-schema.sql` update.
- **Tracing module** (`app/Modules/Platform/Tracing/`): new `SpanProcessor` that persists spans on `onEnd`; `TracerProviderFactory` chains it alongside the exporter; `config/tracing.php` gains `persist` and `slow_span_threshold_ms` keys.
- **Observability UI**: new `app/Modules/Platform/Observability/` services (`TraceService`, `TraceSpanService`, `ServiceMapService`) and ValueObjects; new Filament pages under `app/Filament/Pages/` attached to the existing `ObservabilityCluster`; new `lang/en|id/admin/observability-traces-page.php` files.
- **Permissions**: `ShieldSeeder` gains `View:Traces` and `View:ServiceMap`.
- **Tests**: new tests mirroring `TracingTest`, `ObservabilityServiceTest`, and the Filament `*AuthorizationTest` pattern; `phpunit.xml` unchanged (same MySQL test connection).
- **Dependencies**: no new Composer or npm packages.