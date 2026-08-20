## Context

Phase 1 (Issue #57) added the Observability cluster, Overview page, `ObservabilityService`, and the `View:Observability` permission. Correlation IDs already flow through:
- `integration_logs.correlation_id` — indexed column with `started_at`/`finished_at`/`duration_ms`/`status`
- `activity_log.properties` JSON (`correlation_id` key) written by `AuditLogService`
- Queue failed-job payloads (`illuminate:log:context.correlation_id`) read by `QueueMonitorService`

Phase 2 must surface executions (one correlation ID = one execution) and a per-execution timeline, aggregating these three existing sources read-only. See proposal.md for the "why".

## Goals / Non-Goals

**Goals:**
- Executions list page under `Platform → Observability`, searchable by correlation ID.
- Execution detail page with a timeline of actual events ordered by recorded start time.
- Aggregate integration, audit, and queue events by correlation ID using existing indexes/mechanisms.
- Distinguish failed events; handle missing duration/status and empty executions gracefully.
- Reuse the existing `View:Observability` permission and the Phase 1 cluster.

**Non-Goals:**
- No schema change, no new logging, no new correlation mechanism.
- No OpenTelemetry / real tracing (Phase 3) and no waterfall/service-map visualization (Phase 4).
- No full-table loading of any log.

## Decisions

### D1. Executions are virtual aggregations, not a model

There is no `executions` table. An execution is derived: one correlation ID → one execution. A dedicated `ExecutionTimelineService` (in `app/Modules/Platform/Observability/Services/`) resolves an execution from the three sources and builds a normalized event list.

Rationale: matches the existing logs as source of truth; avoids schema and duplication. Alternative (new `executions` table) rejected — the spec forbids schema without strong reason and would create a parallel correlation mechanism.

### D2. Event normalization into a single timeline shape

The service maps each source row into a uniform event array:
`{source, kind, title, description, occurred_at, duration_ms, status, link, correlation_id}` where `occurred_at` is the best real timestamp available per source (`started_at` for integration, `created_at` for audit). The timeline sorts by `occurred_at` ascending.

Integration events expose sanitized fields (provider, endpoint, method, status, status_code, duration_ms) and link to `ViewIntegrationLog`. Audit events expose description/event/subject and link to `ViewAuditLog`. Queue events expose job name and failed_at. Raw request/response headers and bodies are never rendered.

### D3. Correlation search over an indexed surface

The executions list page provides a correlation-ID search. Because `activity_log.properties` is JSON (no index), the list surface is derived primarily from `integration_logs` (indexed `correlation_id`), which is the reliable way to enumerate executions without a full scan. When the user enters a specific correlation ID, the detail service resolves all three sources for that ID (including JSON-properties audit lookup) and renders whatever exists — even if only audit/queue events are present.

Limitation documented: audit-only or queue-only executions cannot be *enumerated* in the list (no index), but can still be *resolved* by exact correlation search.

### D4. Page structure: two Filament pages, no Resource

Two pages registered on `ObservabilityCluster`:
- `ObservabilityExecutions` — list/search by correlation ID.
- `ObservabilityExecutionDetail` — timeline for one correlation ID (record param).

A Filament `Resource` is unnecessary since executions are not an Eloquent-backed CRUD model. This matches Phase 1's page-based pattern.

### D5. Reuse `View:Observability` permission

Both pages use `HasPageShield` + `canAccess()` identical to the Overview page. No new permission.

## Risks / Trade-offs

- [Audit JSON lookup is unindexed] → Mitigation: exact-match correlation search per execution only, bounded by the JSON query, never a full scan of the whole table at list time; document the enumeration limitation.
- [Queue events only cover recent failed jobs (Horizon cap)] → Mitigation: queue section shows what Horizon reports; success events are not in scope and are not fabricated.
- [List derived from integration_logs only] → Mitigation: exact search still resolves audit/queue-only executions; empty state explains this.
- [Correlation strings are free-form UUIDs] → Mitigation: treat as opaque strings, sanitize before display/URL encoding.

## Migration Plan

No schema migration. Code is additive behind the existing cluster. Deploy is a normal PR merge; rollback is reverting the PR.

## Open Questions

None that would change specs, approach, or tasks.