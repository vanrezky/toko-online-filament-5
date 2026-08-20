## 1. Service Layer

- [x] 1.1 Create `App\Modules\Platform\Observability\ValueObjects\ExecutionTimeline` value object holding correlation ID, normalized events, and source counts.
- [x] 1.2 Create `App\Modules\Platform\Observability\Services\ExecutionTimelineService` resolving events for one correlation ID from integration logs (indexed lookup), audit logs (JSON properties lookup), and queue failed jobs (Horizon payload lookup).
- [x] 1.3 Normalize each source row into a uniform event shape `{source, kind, title, description, occurred_at, duration_ms, status, link, correlation_id}` ordered by occurred_at ascending.
- [x] 1.4 Handle missing duration and missing status without fabricating values; keep raw request/response payloads out of the timeline.

## 2. Executions List Page

- [x] 2.1 Add `ObservabilityExecutions` page under `ObservabilityCluster` exposing a correlation-ID search and an execution list derived from indexed integration-log correlations.
- [x] 2.2 Enumerate executions from `integration_logs` distinct correlation IDs (bounded, ordered by most recent), with an empty state when none match.
- [x] 2.3 Add authorization via `HasPageShield` + `canAccess()` reusing `View:Observability`.
- [x] 2.4 Add en/id translation keys for the list page, columns, and empty state.

## 3. Execution Detail Page

- [x] 3.1 Add `ObservabilityExecutionDetail` page under `ObservabilityCluster` accepting a correlation ID record param.
- [x] 3.2 Render the normalized timeline from `ExecutionTimelineService`, ordering by occurred_at ascending and marking failed events distinctly.
- [x] 3.3 Link integration events to `ViewIntegrationLog` and audit events to `ViewAuditLog`; render queue events inline.
- [x] 3.4 Render a graceful empty state when the execution has no correlated events.
- [x] 3.5 Add en/id translation keys for the detail page, event kinds, and empty state.

## 4. Tests

- [x] 4.1 Add `ExecutionTimelineServiceTest` covering: same correlation groups events, different correlations stay separate, timeline ordering, duration display, missing duration, missing status, failed event handling, and empty execution.
- [x] 4.2 Add authorization tests for the Executions list and detail pages (superuser, permission-granted, unauthorized).
- [x] 4.3 Add correlation search test verifying the list resolves an execution and that exact search renders the timeline.
- [x] 4.4 Add a test asserting sensitive data (request/response headers/bodies, credentials) is not rendered in the timeline.

## 5. Validation

- [x] 5.1 Run Pint and static analysis on all new/modified files.
- [x] 5.2 Run new tests plus the existing Platform and Filament suites.
- [x] 5.3 Run `npm run build` to verify frontend integrity.