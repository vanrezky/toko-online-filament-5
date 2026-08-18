## 1. Package and Health Engine Setup

- [ ] 1.1 Add the Laravel-11-compatible `spatie/laravel-health` dependency, inspect its resolved APIs, and publish/register only required configuration, provider, and package-owned persistence assets.
- [ ] 1.2 Configure the package's official application, database, Redis/cache, queue-processing, and used-disk-space checks with safe disk thresholds and environment overrides.
- [ ] 1.3 Configure package-supported result persistence and schedule the official health execution command through the existing Laravel scheduler at the appropriate cadence.

## 2. Platform Health Boundary

- [ ] 2.1 Create `app/Modules/Platform/Health` and implement HealthMonitorService with a normalized, safe overall summary backed by the installed Spatie Health APIs.
- [ ] 2.2 Add safe application informational metadata and exception/no-result fallbacks without exposing credentials, secrets, or stack traces.

## 3. Filament System Health

- [ ] 3.1 Add the native `Platform` / `System Health` Filament page that consumes HealthMonitorService and renders overall state, required summaries/details, and a single Refresh Health action.
- [ ] 3.2 Reuse the superuser/Shield authorization convention, seed `View:SystemHealth`, and add Indonesian/English translation keys.

## 4. Verification and Operations

- [ ] 4.1 Add tests for HealthMonitorService normalization, unavailable results, System Health authorization, and safe failed-state rendering using package-supported fakes or mocks.
- [ ] 4.2 Document local and production health configuration, scheduler prerequisites, result persistence, queue-check behavior, and safe refresh usage.
- [ ] 4.3 Run Pint, focused tests, frontend build if presentation assets change, package/scheduler validation, OpenSpec strict validation, and diff checks.
