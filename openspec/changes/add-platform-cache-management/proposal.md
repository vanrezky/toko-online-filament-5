## Why

Administrators currently have no safe Platform view for diagnosing application cache connectivity or clearing stale cache. Existing cache usage is split across direct Laravel Cache calls and `CacheService`, while Redis also serves queue and Horizon workloads, so broad Redis flush operations could disrupt unrelated runtime data.

Issue: #73

## What Changes

- Add a Platform `Cache Management` page with a safe cache overview and lightweight connection status.
- Add an application-cache registry so maintenance actions target cache entries created by the application rather than arbitrary Redis keys.
- Add a guarded clear-application-cache action using Laravel cache abstraction and registered entries only.
- Add dedicated view and management permissions, with superuser access consistent with existing Platform pages.
- Record cache maintenance actions through the existing `AuditLogService`.
- Correct cache connectivity handling so the configured application cache store/connection is checked, not an unrelated Redis connection.
- Add regression and authorization tests covering isolation from queue, Horizon, session, and other Redis data.

## Capabilities

### New Capabilities

- `platform/cache-management`: Operational visibility and safe maintenance for application-managed cache.

### Modified Capabilities

- None.

## Impact

- Filament Platform page, translations, Shield permissions, and admin tests.
- Cache service and application cache call sites that need registration through the managed cache abstraction.
- Existing Platform audit and health services, reused without creating a second health system.
- Redis configuration boundaries remain unchanged: application cache is distinct from queue/Horizon Redis usage.
- No public API or database schema changes are expected.
