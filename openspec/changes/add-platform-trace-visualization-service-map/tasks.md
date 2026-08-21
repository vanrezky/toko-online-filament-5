## 1. Configuration

- [x] 1.1 Add `persist` (env `TRACING_PERSIST`, default `false`) and `slow_span_threshold_ms` (env `TRACING_SLOW_SPAN_THRESHOLD_MS`, default `1000`) keys to `config/tracing.php` with documentation comments
- [x] 1.2 Add the two new env keys to `.env.example` with explanatory comments

## 2. Schema and model

- [x] 2.1 Create migration `2026_08_21_000000_create_tracing_spans_table.php` with columns `trace_id` (char 32), `span_id` (char 16), `parent_span_id` (char 16 nullable), `name` (string), `kind` (unsigned smallint), `status_code` (nullable string), `status_description` (nullable text), `start_ns` (unsigned bigint), `end_ns` (unsigned bigint nullable), `duration_ms` (unsigned integer nullable), `attributes` (json nullable), `events` (json nullable), `correlation_id` (uuid nullable), `operation` (string nullable), timestamps; unique index on `span_id`, indexes on `trace_id`, `parent_span_id`, `correlation_id`, `(trace_id, start_ns)`, `created_at`
- [x] 2.2 Create `App\Modules\Platform\Tracing\Models\TracingSpan` Eloquent model with JSON casts for `attributes` and `events`, date casts, and a `$fillable`/`$guarded` policy consistent with `IntegrationLog`
- [x] 2.3 Update `database/schema/mysql-schema.sql` with the new `tracing_spans` table definition

## 3. Persistence processor

- [x] 3.1 Create `App\Modules\Platform\Tracing\Support\SpanAttributeFilter` (or extend `TracingAttributes`) to filter a `SpanDataInterface` attribute set down to the allow-list
- [x] 3.2 Create `App\Modules\Platform\Tracing\SpanProcessors\DbSpanProcessor` implementing `SpanProcessorInterface`: `onStart` inserts a row (null `end_ns`/status), `onEnd` updates by `span_id` with `end_ns`, `duration_ms`, `status_code`, `status_description`, `events`; all DB errors caught, reported, and swallowed (best-effort)
- [x] 3.3 Wire `DbSpanProcessor` into `TracerProviderFactory::create()` as a chained processor (array) when `tracing.persist` is enabled, for both production and testing branches
- [x] 3.4 Ensure `TraceManager::exportedSpans()` still works unchanged with the chained processor (forceFlush path)

## 4. Services and value objects

- [x] 4.1 Create `App\Modules\Platform\Tracing\Services\TraceService` with `list()` (paginated, `GROUP BY trace_id`, filters for time range/status/operation/duration/error-presence/correlation ID, search by trace ID or correlation ID, ordered by latest start time) and `resolve(string $traceId)` returning a `TraceDetail` value object
- [x] 4.2 Create readonly value objects `TraceSummary`, `TraceDetail`, `TraceSpan` (with `TraceSpan` carrying allow-listed attributes, events, status, timestamps, correlation ID, operation, and Integration Log linkage fields) under `App\Modules\Platform\Tracing\ValueObjects`
- [x] 4.3 Create `App\Modules\Platform\Tracing\Services\TraceSpanService` that builds the span tree from `parent_span_id` and computes per-span waterfall geometry (left offset % and width % from actual start/end nanos relative to trace start; zero-duration spans render a minimal visible bar)
- [x] 4.4 Create `App\Modules\Platform\Tracing\Services\ServiceMapService` producing nodes and edges derived from recorded parent→child relationships and DB-aggregated metrics (`requests`, `error_rate`, `avg`, `P95`) for a given range (1h/24h/7d), computing metrics at the backend only when calculable; create supporting `ServiceMapNode`/`ServiceMapEdge` value objects

## 5. Permissions

- [x] 5.1 Add `View:Traces` and `View:ServiceMap` permissions to `database/seeders/ShieldSeeder.php` alongside the existing `View:*` permissions
- [x] 5.2 Register the two new permissions in the Filament Shield config (if discovered dynamically) or confirm the seeder covers them

## 6. Filament pages

- [x] 6.1 Create `App\Filament\Pages\ObservabilityTraces` page under `ObservabilityCluster` (slug `traces`, `HasPageShield`, `canAccess()` = `is_super_user` OR `View:Traces`), with filters form (time range, status, operation, duration, error presence, correlation ID), search box (trace ID/correlation ID), and paginated table; expose `getViewData()` calling `TraceService`
- [x] 6.2 Create `App\Filament\Pages\ObservabilityTraceDetail` page (slug `traces/{trace}`) with header summary (root operation, trace ID, correlation ID, duration, status, span count, error count), partial-trace label when any span has null `end_ns`, actions to copy correlation ID / view related Integration Logs / view related Audit Logs, and `getViewData()` calling `TraceService` + `TraceSpanService`
- [x] 6.3 Create `App\Filament\Pages\ObservabilityServiceMap` page (slug `service-map`, `View:ServiceMap`), time-range presets (1h/24h/7d), `getViewData()` calling `ServiceMapService`
- [x] 6.4 Register the three pages in `AdminPanelProvider` via `->pages(...)` (and any discoverPages/config needed)

## 7. Blade views

- [x] 7.1 Create `resources/views/filament/pages/observability-traces.blade.php` with a paginated trace table (timestamp, trace ID, correlation ID, root operation, status, duration, span count, error count), partial-trace badges, links to detail page, and filters rendered from the form
- [x] 7.2 Create `resources/views/filament/pages/observability-trace-detail.blade.php` with header summary, copy/action buttons, Integration Log / Audit Log links, a CSS waterfall (span rows with server-computed offset/width bars colored by status, slow spans marked via threshold), a nested span tree, and a span detail panel (trace ID, span ID, parent span ID, operation, start/end, duration, status, sanitized attributes/events)
- [x] 7.3 Create `resources/views/filament/pages/observability-service-map.blade.php` with a read-only server-rendered diagram and per-node/edge metrics table (requests, error rate, avg, P95), empty states when not calculable, responsive with horizontal scroll
- [x] 7.4 Ensure the waterfall/tree/map use only server-computed values and never render fabricated spans/nodes/metrics

## 8. Localization

- [x] 8.1 Create `lang/en/admin/observability-traces-page.php` and `lang/id/admin/observability-traces-page.php` with title, navigation label, filters, ranges, columns, statuses, and action labels
- [x] 8.2 Create `lang/en/admin/observability-trace-detail-page.php` and `lang/id/admin/observability-trace-detail-page.php`
- [x] 8.3 Create `lang/en/admin/observability-service-map-page.php` and `lang/id/admin/observability-service-map-page.php`

## 9. Integration Log / Audit Log linkage

- [x] 9.1 In trace list/detail, resolve Integration Log URLs filtered by correlation ID (mirroring `IntegrationLogResource::getUrl('index')?filters[search][value]=...`) and Audit Log URLs when supported
- [x] 9.2 Add copy-correlation-ID action and verify it copies the value to the clipboard

## 10. Tests

- [x] 10.1 Add `tests/Feature/Platform/Tracing/DbSpanProcessorTest.php` covering onStart insert / onEnd update, allow-list filtering, no sensitive attributes persisted, persistence disabled writes nothing, and DB failure is swallowed
- [x] 10.2 Add `tests/Feature/Platform/Tracing/TraceServiceTest.php` covering list ordering/pagination, all filters, search by trace/correlation ID, resolve by trace ID, and partial-trace detection
- [x] 10.3 Add `tests/Feature/Platform/Tracing/ServiceMapServiceTest.php` covering node/edge derivation from recorded relationships, metrics aggregation (requests, error rate, avg, P95), metrics hidden when not calculable, and time-range filtering
- [x] 10.4 Add `tests/Feature/Filament/ObservabilityTracesAuthorizationTest.php`, `ObservabilityTraceDetailAuthorizationTest.php`, and `ObservabilityServiceMapAuthorizationTest.php` mirroring the existing `*AuthorizationTest` pattern (super user, granted permission, plain user → forbidden; content assertions)
- [x] 10.5 Verify `tests/Feature/Platform/Tracing/TracingTest.php` still passes unchanged (persist defaults off)

## 11. Validation

- [ ] 11.1 Run `./vendor/bin/sail test` (or `./vendor/bin/phpunit`) and ensure the full suite passes (blocked by nine pre-existing failures/errors in unrelated StoreAccessMode, ContactMessage, Example, Payroll, and Audit tests)
- [x] 11.2 Run `npm run build` to confirm no frontend regression
- [x] 11.3 Run `php artisan shield:generate` or equivalent to confirm new permissions resolve, and run static analysis/lint if configured
- [x] 11.4 Manually verify in the browser at http://localhost:81/admin: with `TRACING_ENABLED=true` and `TRACING_PERSIST=true`, a request produces persisted spans, the trace list shows them, the detail renders a truthful waterfall/tree, and the service map aggregates without fabricated data
