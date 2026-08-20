## Context

The application is a Laravel 11 modular monolith with a Filament admin, Horizon, Spatie Health, and Platform modules for Queue, Health, Audit, and Integration. Platform access follows a super-user-or-Shield-permission convention (`is_super_user || can('View:<Surface>')`), with `ShieldSeeder` creating each `View:*` permission. The existing Platform surfaces are Queue Monitor (sort 1), System Health (sort 2), Audit Logs (sort 3), and Integration Logs (sort 4), all under the `Platform` navigation group.

`integration_logs` already carries the fields needed for aggregation — `status`, `provider`, `duration_ms`, `correlation_id`, `started_at`, `finished_at`, `created_at` — and is indexed on `created_at`, `(provider, status)`, `direction`, and `correlation_id`. Queue summary data is available from `QueueMonitorService::snapshot()` and health from `HealthMonitorService::summary()`. The correlation ID links integration logs and audit logs without extra schema.

The change must only read existing data. It must not add new logging, metrics, trace, real-time, or schema mechanisms. See proposal.md for motivation and the spec for requirements.

## Goals / Non-Goals

**Goals:**

- One authorized Filament surface aggregating integration, queue, and health signals over a selectable time range.
- Bounded aggregate queries that stay within existing indexes and never load full tables.
- Correlation drill-down reusing the existing Integration Logs resource.
- Reuse of existing queue/health snapshot services and the established Shield authorization convention.

**Non-Goals:**

- New logging, metrics collection, tracing, or real-time transport.
- Invented application/request metrics not present in current data (e.g., RPS, percentiles not computable from persisted columns).
- Changes to existing Platform pages or queue/health service implementations.
- Database schema or index changes; no new migrations.

## Decisions

### Platform-local observability module

Create `App\Modules\Platform\Observability` with an `ObservabilityService` and an `ObservabilitySnapshot` value object. The service computes all integration metrics and reuses `QueueMonitorService` and `HealthMonitorService` for the queue/health summaries. Keeping the aggregation logic in a service (not in the page) mirrors the existing Platform convention (Health/Queue services feed pages) and keeps the Filament layer thin and testable without a browser.

Alternative: compute inline in the page. Rejected because it mixes query logic with presentation and makes unit testing harder, contrary to the established pattern.

### Time-range filter

The page exposes presets 24h / 7d / 30d plus a custom date-range option. The service accepts a start (`from`) and end (`until`) boundary and applies `whereBetween('created_at', ...)`. Default range is the last 24 hours. All queries use the `created_at` index for range bounding.

Alternative: filter on `started_at`. Rejected because `created_at` is indexed and represents persistence time consistently; `started_at` is not indexed.

### Bounded aggregate queries

The service uses only aggregate queries:

- `count()` for total calls, with `where status = failed` for failures.
- `AVG(duration_ms)` and `MAX(duration_ms)` for duration cards.
- `GROUP BY provider` with `COUNT`, `SUM(status = 'failed')`, and `AVG(duration_ms)` for the provider table.
- `ORDER BY duration_ms DESC LIMIT 10` for slowest calls.
- `ORDER BY created_at DESC LIMIT 10` with `status = failed` for recent errors.

Every query is bounded by the range and by explicit limits; no `IntegrationLog::all()` or unbounded scans. These stay within the existing `created_at` and `(provider, status)` indexes.

Alternative: compute percentiles (P95) for provider latency. Rejected because the persisted logs have no ordering index per provider beyond duration ordering at query time; a P95 across a provider group requires loading full durations for that range, which violates the no-full-table-load constraint. Provider table therefore shows average duration only.

### Filament presentation

Add an `ObservabilityCluster` (`navigationGroup = 'Platform'`) containing a single `ObservabilityOverview` page, producing the requested `Platform → Observability → Overview` navigation path. The page uses `HasPageShield`, `canAccess()` = `is_super_user || can('View:Observability')`, `getViewData()` returning the snapshot, and a custom Blade view following the System Health / Queue Monitor pattern. Stat cards, provider table, slow-calls table, and recent-errors list render with explicit empty states.

Alternative: a flat page in the Platform group. Rejected because the requested navigation path is explicitly `Platform → Observability → Overview`, which requires the cluster.

### Correlation drill-down

Each listed row exposes its `correlation_id`. Activating it navigates to the existing Integration Logs resource pre-filtered by that correlation ID (the resource already supports a correlation-ID search/filter). This reuses the existing indexed `correlation_id` column and requires no new link infrastructure.

### Authorization and permission seeding

Add `View:Observability` to `ShieldSeeder` via the established `Utils::getPermissionModel()::findOrCreate('View:Observability', 'web')` pattern. No role is created; the permission is assigned through existing role administration.

## Risks / Trade-offs

- [Provider grouping with many providers grows row counts] → The provider query is `GROUP BY provider` over a range-bounded set; provider count is small and bounded by range, and the range filter caps the scanned set.
- [Large 30-day ranges scan many rows] → Queries use indexed `created_at` bounds and aggregate server-side; slow-calls and recent-errors use explicit `LIMIT`.
- [Empty data makes dashboard look broken] → All sections render explicit empty states, and metric cards render zeros.
- [Custom ranges without dates] → The page falls back to the default 24h range when the custom range is incomplete.
- [View:Observability not assigned to any role initially] → Superusers retain access; admins grant the permission via existing role administration, matching the other Platform surfaces.

## Migration Plan

1. Add `View:Observability` to `ShieldSeeder` and run the seeder.
2. Deploy code (service, cluster, page, view, translations) together; no schema migration is involved.
3. Rollback is a code-only revert; removing the permission and page restores prior behavior without data changes.

## Open Questions

- None.