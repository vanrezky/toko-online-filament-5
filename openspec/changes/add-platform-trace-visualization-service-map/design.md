# Design: Trace visualization and service map (Phase 4)

## Context

See proposal.md — Why. Phase 3 (`platform-opentelemetry-tracing`) exports ended spans over OTLP or keeps them in-process for tests; nothing is queryable from the application. Issue #62 requires an in-admin trace list/detail UI and a backend-aggregated service map, which both demand persisted, queryable span data. Specs: `platform-trace-visualization`, `platform-trace-service-map`, and the new persistence requirement added to `platform-opentelemetry-tracing`.

Constraints verified in the codebase:

- `TracerProvider::__construct` accepts `SpanProcessorInterface|array`, so a persistence processor can be chained alongside the exporter (`TracerProviderFactory::create`, `app/Modules/Platform/Tracing/Services/TracerProviderFactory.php:50`).
- `SpanProcessorInterface::onStart(ReadWriteSpanInterface)` and `onEnd(ReadableSpanInterface)` receive SDK spans exposing `getContext()`, `getParentContext()`, `getName()`, `getKind()`, `getDuration()`, `getAttribute()`, and `toSpanData(): SpanDataInterface` (`getTraceId`, `getSpanId`, `getParentSpanId`, `getStatus`, `getStartEpochNanos`, `getEndEpochNanos`, `getAttributes`, `getEvents`).
- Span/`parent` IDs are 32/16-char hex strings, not UUIDs.
- The admin panel is 100% Blade/Livewire (Filament v5.6); there is no admin JS bundle and no chart/graph library in `package.json`. The storefront Vue/Inertia app does not mount on admin pages.
- Existing patterns to mirror: `ObservabilityExecutions` page (form + `getViewData()` + Blade table), `ObservabilityService` (DB aggregation into readonly ValueObjects), `integration_logs` migration (JSON columns, explicit indexes), `HasPageShield` + `canAccess()` gating, `lang/en|id/admin/observability-*-page.php`.

## Goals / Non-Goals

Goals:

- Persist allow-listed span data so traces are queryable by `trace_id` and aggregatable by the DB.
- Provide trace list/detail (waterfall, span tree, span detail) and a read-only service map in the Filament admin, all rendered in Blade.
- Keep tracing best-effort: persistence failure never breaks the request.
- Keep sensitive-data guarantees identical to Phase 3 (allow-list only).

Non-Goals (design-level boundaries; see proposal for scope):

- No new JS bundle for the admin panel and no new npm/composer dependencies. The waterfall and service map are server-rendered (Blade + CSS/SVG).
- No OTLP/collector dependency for the UI to function.
- No live/polling updates; pages render from persisted data at request time.
- No workflow/drag-and-drop editing of the map.

## Decisions

### 1. Persist spans via a DB span processor chained alongside the exporter

A new `DbSpanProcessor implements SpanProcessorInterface` is added in `TracerProviderFactory::create()` as a second processor when `tracing.persist` is enabled. It writes to the main MySQL connection through the `TracingSpan` model.

- `onStart(ReadWriteSpanInterface $span)`: insert a row with `trace_id`, `span_id`, `parent_span_id`, `name`, `kind`, `start_ns`, allow-listed `attributes` (from `toSpanData()`), `correlation_id`, and `operation`, leaving `end_ns`/`duration_ms`/`status` null.
- `onEnd(ReadableSpanInterface $span)`: update the row by `span_id` with `end_ns`, `duration_ms`, `status_code`, `status_description`, and `events`.

Rationale: an insert-on-start/update-on-end design means spans that start but never end (e.g., process killed mid-trace) remain in the DB with a null `end_ns`, which is exactly what lets the UI label such traces "Partial trace" honestly. OTel never calls `onEnd` for a span that never ends, so end-time-only persistence could not represent partial traces.

Alternatives considered:

- **End-time-only persistence** (insert in `onEnd`): simpler, but cannot represent traces with never-ended spans; rejected because the spec requires honest partial-trace labeling.
- **Dedicated `tracing` DB connection**: rejected — the app has one `mysql` connection and `tracing` config uses it; keeping a single connection preserves transactional/teardown behavior in tests.

### 2. Schema: single `tracing_spans` table

One table stores every span (no separate `tracing_traces` summary table). `trace_id` is indexed and the trace detail page is a point lookup; the trace list derives summaries via a single `GROUP BY trace_id` query with per-page limits. Columns: `id`, `trace_id` (char 32), `span_id` (char 16, unique), `parent_span_id` (char 16, nullable), `name` (string), `kind` (unsigned smallint), `status_code` (nullable), `status_description` (nullable text), `start_ns`, `end_ns` (nullable), `duration_ms` (nullable), `attributes` (json, allow-listed), `events` (json, nullable), `correlation_id` (uuid, nullable), `operation` (string, nullable), timestamps. Indexes: `trace_id`, `span_id` (unique), `parent_span_id`, `correlation_id`, `(trace_id, start_ns)`, `created_at`.

Rationale: matches the `integration_logs` precedent (JSON columns, explicit indexes), keeps P95 aggregation a `GROUP BY`/percentile query, and avoids the complexity of keeping a denormalized trace summary in sync.

### 3. Only allow-listed attributes are persisted

The processor stores only attributes present in `TracingAttributes::all()` plus the denormalized `operation` and `correlation_id` columns. The restricted set (headers, bodies, SQL, bindings, tokens, card data, raw payloads) is never written.

### 4. Trace list and detail via services + Blade, no new frontend bundle

- `TraceService`: `list()` (paginated, `GROUP BY trace_id`, filters: time range, status, operation, duration, error-presence, correlation ID; search by trace ID/correlation ID) and `resolve(string $traceId)` returning a `TraceDetail` ValueObject (header fields + spans ordered by start time).
- `TraceSpanService`: builds the span tree from `parent_span_id` and computes per-span waterfall geometry (left offset % and width % derived from actual `start_ns`/`end_ns` relative to trace start; duration 0 → minimal bar so it is visible but never fabricated).
- `ServiceMapService`: DB-level aggregation over the selected range producing nodes (services/operations) and edges derived from parent→child relationships across recorded spans, with `requests`, `error_rate`, `avg`, `P95`; metrics computed at the backend only when calculable.
- Filament pages `ObservabilityTraces` (slug `traces`), `ObservabilityTraceDetail` (slug `traces/{trace}`), and `ObservabilityServiceMap` (slug `service-map`) under `ObservabilityCluster`, gated by `View:Traces` / `View:ServiceMap`.

Rationale: matches the existing `getViewData()` + Blade-table page pattern exactly, satisfies the Issue's constraints (no graph library, no separate frontend app, Filament remains the shell), and keeps the waterfall/span-tree/span-detail renderable with server-computed CSS widths.

Alternatives considered:

- **A Vue component mounted in a Filament page** (new Vite input + `Js::make()` + JSON payload): feasible but introduces the first Vue-in-Filament coupling and a second admin JS bundle; rejected because Blade + CSS satisfies the interaction needs (hover/expand) with zero new build surface.
- **vis-network / cytoscape / d3 service map**: net-new dependencies with bundle-size and maintenance cost; rejected in favor of a server-rendered Blade diagram that still honors "no fabricated nodes."

### 5. Slow-span threshold and persistence are configuration-driven

`config/tracing.php` gains `persist` (env `TRACING_PERSIST`, default `false`, so existing OTLP-only behavior is unchanged) and `slow_span_threshold_ms` (env `TRACING_SLOW_SPAN_THRESHOLD_MS`, default `1000`). The threshold is read at render time by the trace detail page.

### 6. Permissions seeded independently

`ShieldSeeder` gains `View:Traces` and `View:ServiceMap`. Pages use the existing `HasPageShield` + `canAccess()` pattern (`is_super_user` OR `can(...)`), identical to the other Observability pages. No permission changes to existing pages.

## Risks / Trade-offs

- **Write amplification on every span when persistence is enabled** → `persist` defaults off; when on, the processor catches all DB exceptions (`report()` + continue), never propagating into the request; `onStart` insert and `onEnd` update are cheap single-row ops on indexed keys.
- **Synchronous DB write in the hot path** → acceptable for this app's volume; spans are written inline, but any failure is swallowed (best-effort, matching Phase 3's exporter tolerance). If volume grows, a queue-backed processor can replace it without spec changes.
- **Partial-trace accuracy** → a span that never ends stays as an insert-only row with null `end_ns`; the UI marks such traces partial. Rows for spans that never ended are intentionally kept, not purged.
- **`database/schema/mysql-schema.sql` staleness** → the file already lags behind recent migrations (`integration_logs`, health tables); the migration is the source of truth and the schema file is updated in the same change.
- **P95 accuracy** → computed with a bounded percentile query (e.g., `PERCENTILE_CONT` where supported, else a backend approximation over an aggregated/sampled set); metric shown only when calculable, per spec.
- **Unique `span_id` collisions across re-runs** → `span_id` is unique per persisted row; an upsert-on-end update by `span_id` guards against duplicate insertion.

## Migration Plan

1. Add `tracing.persist` + `tracing.slow_span_threshold_ms` to `config/tracing.php` (defaults off / 1000) and to `.env.example` (documented).
2. Run the new `create_tracing_spans_table` migration; update `database/schema/mysql-schema.sql`.
3. Deploy the processor gated by `tracing.persist` (no behavior change until enabled).
4. Enable `TRACING_PERSIST=true` in environments that want the UI populated.

Rollback: setting `TRACING_PERSIST=false` (or `TRACING_ENABLED=false`) stops all writes; the UI pages show empty states. Dropping the migration rolls back the schema.

## Open Questions

None — the remaining unknowns (exact P95 SQL dialect behavior, service-map edge classification) are resolvable during implementation without changing the specs or the chosen approach.