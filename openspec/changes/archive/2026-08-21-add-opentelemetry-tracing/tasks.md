# Tasks: Add OpenTelemetry Tracing

## 1. Dependencies & Config

- [x] 1.1 Add `open-telemetry/sdk`, `open-telemetry/api`, and `open-telemetry/exporter-otlp` to `composer.json` and install.
- [x] 1.2 Create `config/tracing.php` with `enabled` (default false), `service_name`, `service_version`, `environment`, `sampler_ratio`, `exporter` (otlp/memory), `exporter_endpoint`, `protocol`, and per-source toggles (`http`, `http_client`, `database`, `queue`), each reading `OTEL_*` / `TRACING_*` env vars.

## 2. Tracing Module

- [x] 2.1 Create `App\Modules\Platform\Tracing` module: `TracerProviderFactory` (builds provider from config, selects `SimpleSpanProcessor` in testing / `BatchSpanProcessor` otherwise, supports in-memory and OTLP exporters), `TraceContext` (current tracer + guard helper), and `TraceManager` service exposing guarded `startRootSpan` / `endRootSpan` / `span` helpers that never throw.
- [x] 2.2 Register the tracer provider and trace manager in a `TracingServiceProvider` that only activates when `tracing.enabled` is true; bind nothing that connects to a network when disabled.

## 3. HTTP Request Instrumentation

- [x] 3.1 Add `TracingMiddleware` (HTTP middleware) that starts a root span for the request (method, route/path, correlation ID attribute), records the response status code and duration, records unhandled exceptions, and always ends the span in `finally`. Register it in the global middleware stack after `CorrelationIdMiddleware` so the correlation ID is set first.

## 4. HTTP Client Instrumentation

- [x] 4.1 Add a Guzzle middleware (`TracingHttpMiddleware`) registered on the application HTTP client (`Http::macro` or `stack` in the service provider) that starts a child span per outbound request recording method, URL, status code, and duration — never headers, bodies, or payloads.

## 5. Database Instrumentation

- [x] 5.1 Add a `DatabaseQueryListener` listening to `Illuminate\Database\Events\QueryExecuted` that derives `db.system`, `db.operation` (query type), `db.namespace` (table, parsed from the SQL prefix with a guarded parser), and duration; never records SQL text or bindings. Register it only when `tracing.database` is enabled.

## 6. Queue Job Instrumentation

- [x] 6.1 Add a queue job middleware (`TracingJobMiddleware`) that starts a span when a correlation ID is active and ends it after the job runs; no span when no correlation ID. Register it globally for dispatched jobs.

## 7. Sensitive-Data & Resilience

- [x] 7.1 Add a central attribute allow-list and assert every instrumentation only records allow-listed attributes.
- [x] 7.2 Ensure all tracing entry points swallow and log (best-effort) any thrown error so a tracing failure never breaks a request or job.

## 8. Tests

- [x] 8.1 HTTP span test: request produces a root span with method, route, status, duration, and correlation ID attribute (sampling 1.0).
- [x] 8.2 Exception recording test: unhandled exception is recorded on the root span.
- [x] 8.3 HTTP client test: outbound call produces a child span with method/URL/status/duration and no headers or bodies.
- [x] 8.4 Database test: query produces a child span with operation/table/duration and no SQL text or bindings.
- [x] 8.5 Queue test: job with correlation ID produces a span; job without correlation ID produces none.
- [x] 8.6 Disabled test: tracing disabled exports no spans and leaves request behavior unchanged.
- [x] 8.7 Exporter-unavailable test: failing exporter does not break the request.
- [x] 8.8 Sampling test: sampler is configured from the ratio (assert ratio 1.0 exports; ratio 0 exports none).
- [x] 8.9 Sensitive-data test: sensitive values present in request/headers/query bindings are never exported.

## 9. Validation

- [x] 9.1 Run Pint on new/modified files.
- [x] 9.2 Run the new tracing test suite plus the existing Platform and Filament suites.
- [x] 9.3 Run `npm run build` to confirm the frontend is unaffected.
- [x] 9.4 Verify existing Integration Logs, Audit Logs, and Horizon tests still pass.