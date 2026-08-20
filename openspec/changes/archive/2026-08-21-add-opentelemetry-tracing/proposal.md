## Why

Platform engineers can reconstruct a business execution from correlation-tagged logs, but they cannot see the true end-to-end path and timing of a request as it flows through HTTP, the database, and the queue. Phase 3 adds real application tracing via OpenTelemetry so the actual execution path is observable and each trace is linkable to the existing correlation ID.

## What Changes

- Add OpenTelemetry tracing to the application using the OpenTelemetry PHP SDK (`open-telemetry/sdk` + `open-telemetry/api` + `open-telemetry/exporter-otlp`) with manual instrumentation — no PECL `ext-opentelemetry` C extension is required, so the existing Sail runtime, Dockerfile, and CI build stay unchanged.
- Add a tracing bootstrap that builds a `TracerProvider` from configuration, creates a root span for each incoming HTTP request (method, route, status code, duration), and records exceptions.
- Capture the correlation ID on every root span so a trace is linkable to the existing correlation-based executions.
- Add child spans for external HTTP client calls via a Guzzle/HTTP middleware so outbound API calls are visible within the trace.
- Add database query spans by listening to query events, capturing query type, affected table(s), and duration without raw SQL text or bound values.
- Add queue job spans so dispatched and processed jobs appear within the trace when a correlation ID is active.
- Make tracing configurable: enabled/disabled flag, sampling ratio, exporter endpoint, service name/version, and which instrumentations are active.
- Ensure exporter failure never breaks the business request (best-effort, guarded).
- Enforce a strict allow-list of attributes so sensitive data (passwords, API keys, Authorization headers, cookies, card data, payment secrets, raw tokens, SQL bound values) is never captured.
- Do not create any new schema; traces are exported out of the application via OTLP and are not stored in the application database.

## Capabilities

### New Capabilities

- `platform-opentelemetry-tracing`: OpenTelemetry-based tracing of HTTP requests, external HTTP calls, database queries, and queue jobs, with correlation-ID linkage, configurable sampling, disable capability, and strict sensitive-data protection.

### Modified Capabilities

- None.

## Impact

- Adds composer dependencies: `open-telemetry/sdk`, `open-telemetry/api`, `open-telemetry/exporter-otlp` (and their transitive dependencies).
- Adds code under `app/Modules/Platform/Tracing` (bootstrap, tracer provider factory, middleware, HTTP middleware, queue job middleware, query listener, config) and a config file `config/tracing.php`.
- Adds `tracing.enabled` (default false) and related environment variables so the feature is opt-in and can be toggled without code changes.
- Adds tests under `tests/Feature` using an in-memory span exporter.
- Does not change existing domain behavior, database schema, Integration Logs, Audit Logs, or Horizon operation.