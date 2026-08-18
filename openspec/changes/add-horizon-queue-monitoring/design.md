## Context

The Laravel 11 application currently dispatches existing jobs through the database queue connection. Redis is already available in Sail and deployment environment variables, while production operations use Supervisor-managed workers and the development deployment workflow restarts a Supervisor worker group. Filament 5 uses Filament Shield and explicitly permits superusers or a `View:<Page>` permission for sensitive pages.

This change introduces Horizon as the Redis queue runtime and detailed technical dashboard, plus a deliberately small Platform Queue area for a read-only operational summary. It must not move existing domain code or alter job behavior.

## Goals / Non-Goals

**Goals:**

- Run Laravel queues on Redis under Laravel Horizon, using the version Composer resolves as compatible with Laravel 11.
- Provide a safe, read-only Queue Monitor summary in Filament under `Platform`.
- Keep metrics acquisition in `App\Modules\Platform\Queue\Services\QueueMonitorService` and use Horizon/Laravel queue repositories or contracts before direct Redis access.
- Restrict the Horizon route and Queue Monitor to `is_super_user` or the existing Filament Shield `View:QueueMonitor` permission.
- Preserve the Supervisor-based deployment model and document a Horizon process configuration and restart procedure.

**Non-Goals:**

- Reorganizing existing MVC domains into modules.
- Managing workers, retrying jobs, or duplicating Horizon tables and controls in Filament.
- Changing job payloads, retry policies, or unrelated queue business rules.
- Introducing a separate Laravel application or a custom queue-monitoring data store.

## Decisions

### Horizon is the Redis runtime and detailed dashboard

Install `laravel/horizon` through Composer and publish its configuration/provider. Configure `QUEUE_CONNECTION=redis`, preserve the existing database connection and failed-job provider for compatibility, and define Horizon environments with a conservative `default` queue. Horizon's generated dashboard remains the deep inspection surface.

**Alternatives considered:** continuing with `queue:work` would not deliver the requested Horizon monitoring; building a custom dashboard would duplicate a maintained Laravel platform feature.

### A narrow Platform Queue service owns the summary

`QueueMonitorService` returns one normalized health snapshot for the Filament page/widgets: Horizon/worker status, pending count, failed count, processed count when Horizon exposes it, active queues, and workload. It obtains Horizon status/workload/metrics through Horizon's installed repository/contracts where available and Laravel queue/failed-job contracts for standard queue data. It will not scan arbitrary Redis keys. A small immutable array/DTO value is acceptable only if it improves the page-test boundary; no generic monitoring framework will be added.

All infrastructure exceptions are caught at the service boundary and represented as explicit `Offline`, `Unavailable`, `No workers`, or zero-value states. Filament renders the returned snapshot and contains no Redis or Horizon queries.

**Alternatives considered:** querying Redis from each widget would couple presentation to storage, multiply calls, and fail hard when Redis is unavailable. Replicating every Horizon metric in Filament would duplicate the purpose of Horizon.

### Authorization reuses the current Filament Shield convention

The Queue Monitor page uses `HasPageShield` and an explicit `canAccess()` check matching the project's sensitive pages: `is_super_user` or `can('View:QueueMonitor')`. Horizon's authorization callback uses the same predicate, so a user cannot bypass the Filament navigation by visiting the Horizon route directly. The implementation will generate/sync the standard Shield page permission rather than create a role system.

**Alternatives considered:** a new role or a route-only middleware would create parallel authorization rules and drift from existing admin policy.

### Supervisor remains the production process manager

The existing Supervisor program is changed from `queue:work` to a single long-running `php artisan horizon` program with suitable autostart/autorestart and group shutdown settings. Deployment continues to restart Supervisor; it must target the configured Horizon group and use `php artisan horizon:terminate` for graceful Horizon restarts where appropriate. The exact program names will stay aligned with the production and development configuration already referenced by deployment workflows.

**Alternatives considered:** systemd/container orchestration would replace the established operating model without a product need. Running Horizon in an SSH session is not durable.

## Risks / Trade-offs

- [Redis outage or credential error] → Queue Monitor catches connection exceptions and renders an unavailable state; Horizon's Supervisor restarts are documented, while failed jobs remain observable through the standard failed-job provider.
- [A production worker still runs `queue:work`] → deployment documentation and workflow changes explicitly replace it with Horizon; rollout requires confirming the Supervisor program on each environment.
- [Horizon repository behavior differs by resolved package version] → pin only a Laravel-11-compatible Composer constraint, inspect the installed public/contract surface, and test the service boundary with mocks/fakes.
- [Metric precision is limited while Horizon is offline] → show unavailable/no-worker labels and zero/unknown values rather than inventing counts.
- [Redis queue migration leaves jobs in the database queue] → deploy only after the existing database queue is drained or consciously handled; rollback restores `QUEUE_CONNECTION=database` and the prior Supervisor command after Horizon is terminated.

## Migration Plan

1. Add the Horizon dependency and publish config/provider without modifying existing domain jobs.
2. Configure Redis queue settings and Horizon environment supervisors; add the protected dashboard and Queue Monitor service/page.
3. Add the Shield page permission, tests, and local/production instructions.
4. In each environment, verify Redis connectivity, drain or account for existing database-queued jobs, set `QUEUE_CONNECTION=redis`, deploy, reload Supervisor, and start/restart Horizon.
5. Verify the Queue Monitor fallback and Horizon access with an authorized and unauthorized user.
6. If rollout fails, terminate Horizon, restore the prior Supervisor `queue:work` command and `QUEUE_CONNECTION=database`, then redeploy the previous configuration. Redis-enqueued jobs require an explicit operational decision before rollback.

## Open Questions

- The repository does not contain the server-side Supervisor configuration for the development worker group; before production rollout, operators must confirm the actual program names and release path on each server.
