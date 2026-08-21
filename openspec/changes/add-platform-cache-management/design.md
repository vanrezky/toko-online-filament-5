## Context

See proposal.md and the `platform/cache-management` requirements. The repository uses Laravel 11, Filament 5, Filament Shield, Spatie Health, Horizon, and a shared Platform AuditLogService. Application cache is configured separately from queue and Horizon Redis connections, but application code currently mixes direct Cache calls with CacheService and CacheService probes the default Redis connection.

## Goals / Non-Goals

**Goals:**

- Provide a small Platform page for safe cache visibility and maintenance.
- Make the application cache boundary explicit and testable.
- Preserve existing queue, Horizon, session, rate limiter, and unrelated Redis data.
- Reuse Platform authorization, health, audit, translations, and Filament page conventions.
- Make cache clear effective for dynamic keys without scanning Redis or accepting arbitrary input.

**Non-Goals:**

- Redis browsing, memory inspection, configuration editing, or command execution.
- Physical deletion of every stale Redis value when logical invalidation is sufficient; expired values remain governed by their existing TTL.
- Replacing the existing System Health page.
- Adding a database table or external dependency.

## Decisions

### Use managed cache groups with versioned namespaces

Introduce a single application-level managed-cache abstraction, extending or replacing the existing `CacheService` boundary, with named groups such as navigation, dashboard, template, regional, voucher, product statistics, frontend content, and shipping. Cache reads and writes in the in-scope application services use a group-aware key namespace.

Clearing a group changes its namespace version through Laravel's cache abstraction. Existing dynamic entries become unreachable immediately while retaining their normal TTL. This avoids Redis `SCAN`, `DEL` loops, `FLUSHDB`, and a persistent manifest that could itself become inconsistent.

The page renders one clear action per declared group and passes only the server-defined group identifier to the service. A global clear-all action may be retained as a separate convenience action, but it is implemented by iterating the same fixed group list rather than accepting user-supplied keys.

Alternative rejected: scanning Redis by prefix. It requires raw Redis commands, is easy to get wrong across client/database prefixes, and turns an application operation into partial Redis administration.

### Treat the configured cache store as the source of truth

The management service resolves the configured default application cache store and its configured connection metadata. Connectivity checks use the resolved cache store, not an unconditional `Redis::ping()` against the default Redis connection. Non-Redis stores are reported using their Laravel cache abstraction without displaying driver secrets.

Alternative rejected: checking the default Redis connection for every cache configuration. In this repository that connection is used by queue and Horizon, so it does not prove that the application cache store is healthy.

### Reuse health result only for matching connection

The page service may consume the existing stored Redis health result when its connection name matches the application cache connection. Otherwise it performs a bounded cache read/write probe and normalizes the result into page-safe data. No new health check registration or result store is introduced.

### Separate view and mutation permissions

Use `View:CacheManagement` for page access and `Manage:CacheManagement` for clear. The page follows the existing `HasPageShield` and superuser conventions; the mutation action also checks the management permission at execution time so hiding/disabling the action is not the only guard.

### Audit only safe metadata

The clear operation uses `AuditLogService` with a non-model operational subject or the existing platform audit pattern, recording group scope and outcome. Cache keys, cache values, connection URLs, credentials, and raw exception messages are excluded. Existing correlation metadata is retained automatically.

### Keep the first UI intentionally small

The page contains an overview panel, connection status, a clear managed cache confirmation action, and safe success/failure notifications. It does not show key lists or add per-key controls. Translation files follow the existing `lang/{locale}/admin/*-page.php` convention.

## Risks / Trade-offs

- [Risk] A newly added cache call may bypass the managed abstraction → Mitigation: centralize the in-scope cache call sites, add tests for group invalidation, and document the managed boundary.
- [Risk] Version keys can leave old values physically stored until TTL expiry → Mitigation: treat clear as logical invalidation, preserve finite TTLs, and explicitly avoid unsafe physical flushes.
- [Risk] A cache store may be unavailable during page rendering → Mitigation: catch connection failures, return safe unavailable snapshots, and keep admin notifications non-sensitive.
- [Risk] Permission names may not be assigned to existing roles automatically → Mitigation: seed the permissions and cover both direct permission and superuser paths in authorization tests.
- [Risk] Existing direct cache keys have inconsistent namespaces → Mitigation: migrate only identified application call sites and preserve behavior through group-specific adapters/tests.

## Migration Plan

1. Add the managed cache abstraction and migrate the identified application cache call sites to declared groups.
2. Add the Platform page, permissions, translations, audit integration, and tests.
3. Deploy normally; no schema migration or Redis flush is required.
4. If rollback is needed, revert the application change. Existing cache values can expire naturally; do not run Redis flush commands as rollback.
