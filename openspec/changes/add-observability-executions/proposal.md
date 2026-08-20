## Why

Platform engineers currently have to open Integration Logs, Audit Logs, and the Queue Monitor separately to reconstruct a single request execution. Correlation IDs already tag events across these sources, but nothing groups them into one timeline. Phase 2 builds that grouping as a read-only view over existing data.

## What Changes

- Add `Platform → Observability → Executions` list page.
- Add execution search by correlation ID.
- Add execution detail page rendering a timeline of actual events ordered by start time.
- Group events from three existing sources by correlation ID:
  - Integration Logs (`integration_logs.correlation_id`, indexed)
  - Audit Logs (`activity_log.properties.correlation_id` JSON)
  - Queue failed jobs (`illuminate:log:context.correlation_id` payload)
- Visual distinction for failed events.
- Graceful handling of empty executions and missing duration/status.
- No new schema, no new logging, no duplicate correlation mechanism. Existing logs stay the source of truth.

## Capabilities

### New Capabilities

- `platform-observability-executions`: Read-only execution timeline grouping integration, audit, and queue events by correlation ID, with search, detail view, and timeline ordering.

### Modified Capabilities

- None.

## Impact

- `app/Modules/Platform/Observability/` — new service and value objects for executions.
- `app/Filament/Clusters/ObservabilityCluster.php` — register Executions pages.
- `app/Filament/Pages/` or `app/Filament/Resources/` — Executions list and detail pages.
- `resources/views/filament/pages/` — timeline Blade view.
- `lang/{en,id}/admin/` — translation keys.
- `database/seeders/ShieldSeeder.php` — `View:Observability` already seeded from Phase 1; reuse, no new permission unless needed.
- Tests: service + authorization + timeline rendering.
