## Why

Platform administrators currently must open System Health, Queue Monitor, Audit Logs, and Integration Logs separately to assess operational health. There is no single view that aggregates integration call volume, failure rates, slow calls, recent errors, queue failures, and health status over a selectable time range, so troubleshooting requires repeated manual cross-referencing.

## What Changes

- Add an authorized Filament `Platform → Observability → Overview` page that aggregates existing operational data into an at-a-glance dashboard.
- Add an isolated `ObservabilityService` (with a snapshot value object) that computes bounded, aggregate metrics from `integration_logs` via `COUNT`/`AVG`/`MAX`/`GROUP BY`, and reuses the existing `QueueMonitorService` and `HealthMonitorService` snapshots for queue/health summaries.
- Add a time-range filter with 24h / 7d / 30d presets and a custom date-range option.
- Show stat cards, a provider performance table (calls, failures, failed rate, average duration), the slowest integration calls, and recent integration errors, each with an empty state.
- Expose each row's correlation ID with a drill-down into the existing Integration Logs resource filtered by that correlation ID.
- Reuse the existing superuser/Filament Shield authorization convention with a new `View:Observability` permission seeded by the established Shield seeder.
- Do not create any new logging, metrics, trace, real-time, or schema mechanism; the change only reads existing data.

## Capabilities

### New Capabilities

- `platform-observability-overview`: Authorized, range-filtered aggregation of integration, queue, and health signals on a single Filament Overview page, with correlation drill-down into existing logs.

### Modified Capabilities

- None.

## Impact

- Adds code under `app/Modules/Platform/Observability` (service + value object) and a small Filament presentation layer (cluster, page, view, translations).
- Seeds one new Shield permission (`View:Observability`) via the existing `ShieldSeeder`.
- Adds Filament feature and service tests under `tests/Feature`.
- Does not change existing domain behavior, Platform pages, queue/health services, database schema, or external integrations.