## Why

Technical operators can inspect queue detail in Horizon and Queue Monitor, but the application has no single, bounded view of whether its core infrastructure is healthy. A platform health capability is needed to detect database, Redis, queue, disk, and application failures safely before they become a broader operational incident.

## What Changes

- Install and configure the Laravel-11-compatible `spatie/laravel-health` package as the authoritative health-check engine.
- Add official package checks for application, database, Redis/cache, queue processing, and disk usage, with package-supported result storage and scheduling where required.
- Add an isolated Platform Health service that normalizes safe health summaries for presentation and contains infrastructure exceptions.
- Add an authorized Filament `Platform` / `System Health` page with overall status, concise check details, and manual refresh.
- Reuse the existing superuser/Filament Shield authorization convention and retain Horizon/Queue Monitor as separate queue-detail surfaces.

## Capabilities

### New Capabilities

- `platform-system-health`: Spatie-backed application health evaluation, safe normalized summaries, and the authorized Filament System Health dashboard.

### Modified Capabilities

- None.

## Impact

- Affects Composer dependencies, application providers/configuration, scheduler registration, package-owned persistence/migrations if required, Filament pages/translations, Shield seeding, and tests.
- Adds code only under `app/Modules/Platform/Health` plus a small Filament presentation layer.
- Does not change existing domain behavior, queue-monitor metrics, or Horizon inspection features.
