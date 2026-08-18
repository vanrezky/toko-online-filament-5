## Context

The Laravel 11 / Filament 5 application now has Redis-backed Horizon and an authorized `Platform` Queue Monitor. Queue Monitor answers operational detail questions, while Horizon provides job-level diagnostics; neither reports the overall health of the application, database, Redis, disk, and queue-processing path. The existing scheduler is defined in `app/Console/Kernel.php`, and privileged Platform pages use a superuser-or-Filament-Shield permission predicate.

The implementation must remain a small extension of the existing `app/Modules/Platform` convention. It must use `spatie/laravel-health` as the health engine, not recreate check execution, persistence, or queue heartbeats.

## Goals / Non-Goals

**Goals:**

- Install the Composer version of `spatie/laravel-health` that resolves against Laravel 11 and inspect its installed API before choosing configuration syntax.
- Use package-provided checks for database connectivity, Redis-backed cache, queue processing, and used disk space wherever available.
- Provide an application identity/availability result with safe Laravel, PHP, and environment metadata; prefer an installed built-in check, and add only the smallest custom check if no built-in represents it.
- Store/schedule health results through the package's supported mechanism when its queue check or result history requires it.
- Expose one safe normalized summary through `HealthMonitorService`, then render it in a native Filament System Health page.
- Reuse `is_super_user || can('View:SystemHealth')` for System Health and use the same technical access category as Queue Monitor/Horizon.

**Non-Goals:**

- Changing existing MVC domain structure, Horizon configuration, or Queue Monitor metrics.
- Building custom Redis scanning, a second queue monitor, a custom health-result table, or a generic observability framework.
- Exposing diagnostics, credentials, stack traces, or secrets through the admin page or public endpoint.

## Decisions

### Spatie Laravel Health is the sole check engine

Install the package, publish only required package config/provider/migration assets, and configure its built-in checks using the API of the resolved package version. Database, cache/Redis, queue, and disk checks will use official checks rather than custom equivalents. The queue check will be configured only with package-supported connection/queue options and scheduled at the package-recommended cadence, so it does not dispatch redundant jobs from a Filament request.

**Alternatives considered:** direct DB/Redis checks in Filament duplicate package behavior and create fragile UI coupling; a Filament health plugin adds a second presentation and configuration layer without need.

### Platform Health service normalizes stored/current results

`App\Modules\Platform\Health\Services\HealthMonitorService` consumes the package's official result store/runner APIs. It produces a compact snapshot: overall status, named checks, safe message, and whitelisted metadata. The service maps package states to `healthy`, `warning`, `failed`, or `unknown`, catches runner/store exceptions, and returns an unknown/unavailable snapshot instead of propagating failures to Filament. It will not re-run expensive or queue-backed checks multiple times in one render.

**Alternatives considered:** returning package result objects directly ties UI to dependency internals; running every check inside every widget refresh can create queue-job spam.

### System Health is a narrow native Filament page

Add `Platform → System Health` using the existing Page, translation, `HasPageShield`, and explicit `canAccess()` patterns. It shows overall state, five required high-level check cards/details, and a `Refresh Health` action that invokes the package-supported run mechanism exactly once per action. It does not display jobs, throughput, workers, or payloads.

**Alternatives considered:** extending Queue Monitor would blur its contract; mirroring the package's own dashboard would add an unnecessary admin surface.

### Schedule and persistence stay package-owned

If the resolved package requires a migration/result store or scheduled Artisan command for historical/queue checks, publish and use those official facilities. Register the package command in the existing Laravel scheduler at the package-supported cadence, guarded with the same single-server convention already used by scheduled commands. No new cron or custom persistence table is introduced.

**Alternatives considered:** an ad hoc scheduled closure and a bespoke table duplicate package responsibility and weaken upgrade compatibility.

### Safe technical authorization and metadata

Create the standard Shield page permission `View:SystemHealth` through the existing seeder, and allow it or `is_super_user`. Whitelist informational metadata such as driver name, latency only when cheaply supplied by the official result, disk percentage, Laravel version, PHP version, and application environment. Suppress exception traces, endpoint credentials, and secret values.

## Risks / Trade-offs

- [Package API differs from remembered documentation] → Composer resolves first; implementation reads installed configuration/check/result APIs and only then writes code.
- [Queue check has intentional asynchronous behavior] → configure and schedule it through the package rather than invoking it on each page render; display its latest result and timestamp.
- [Database or Redis failure also blocks a result store] → service catches errors and emits a safe unknown/failed snapshot; the Filament page remains renderable.
- [Health checks add workload] → use built-ins, package-recommended cadence, one refresh invocation, and no repeated per-card execution.
- [Disk threshold unsuitable for a host] → provide safe env/config overrides with defaults of warning at 70% and failure at 90%.

## Migration Plan

1. Add and inspect the compatible package; publish required package configuration and official storage migration if exposed by the installed version.
2. Configure official checks, result storage, and the existing scheduler; deploy migration/config before enabling scheduled execution.
3. Add Platform Health service, Filament System Health page, Shield permission, translations, and tests.
4. Verify authorized/unauthorized access, persisted/current results, package scheduling, and unavailable states.
5. Roll back by disabling/removing the scheduled package command and reverting package configuration; retain package-owned persisted data unless a deliberate migration rollback is required.

## Open Questions

- The exact package check and result-store method names will be selected from the Composer-resolved package, not assumed from an older documentation version.
- Production must confirm that the existing scheduler is already invoked once per minute; this change will not install or replace cron infrastructure.
