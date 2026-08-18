## 1. Package Health Engine Setup

- [x] 1.1 Add Laravel-11-compatible `spatie/laravel-health` dependency, inspect its resolved APIs, publish/register only required configuration, provider, package-owned persistence assets.
- [x] 1.2 Configure package's official application, database, Redis/cache, queue-processing, used-disk-space checks safe disk thresholds environment overrides.
- [x] 1.3 Configure package-supported result persistence schedule official health execution command through existing Laravel scheduler appropriate cadence.

## 2. Platform Health Boundary

- [x] 2.1 Create `app/Modules/Platform/Health` implement HealthMonitorService normalized, safe overall summary backed by installed Spatie Health APIs.
- [x] 2.2 Add safe application informational metadata exception/no-result fallbacks without exposing credentials, secrets, stack traces.

## 3. Filament System Health

- [x] 3.1 Add native `Platform` / `System Health` Filament page consumes HealthMonitorService renders overall state, required summaries/details, single Refresh Health action.
- [x] 3.2 Reuse superuser/Shield authorization convention, seed `View:SystemHealth`, add Indonesian/English translation keys.

## 4. Verification Operations

- [x] 4.1 Add tests HealthMonitorService normalization, unavailable results, System Health authorization, safe failed-state rendering using package-supported fakes mocks.
- [x] 4.2 Document local production health configuration, scheduler prerequisites, result persistence, queue-check behavior, safe refresh usage.
- [ ] 4.3 Run Pint, focused tests, frontend build if presentation assets change, package/scheduler validation, OpenSpec strict validation, diff checks.
