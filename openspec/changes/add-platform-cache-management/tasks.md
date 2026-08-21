## 1. Cache Boundary

- [x] 1.1 Define the managed application cache groups and document the cache call sites included in each group.
- [x] 1.2 Implement group-aware cache namespace/version handling through the existing application cache service boundary.
- [x] 1.3 Migrate identified application cache producers, including navigation, dashboard, template, regional, voucher, product statistics, frontend content, and shipping caches, to managed groups without changing their business results or TTLs.
- [x] 1.4 Replace the cache availability probe so it resolves and checks the configured application cache store/connection rather than the default Redis connection.
- [x] 1.5 Add unit tests for managed group reads, writes, dynamic keys, invalidation, fallback behavior, and unavailable stores.

## 2. Platform Service and Page

- [x] 2.1 Add a cache management snapshot/service that returns only safe store, prefix, default store, connection, and status metadata.
- [x] 2.2 Reuse a matching existing System Health Redis result when available and use a bounded cache abstraction probe otherwise.
- [x] 2.3 Add the Filament Platform Cache Management page following existing page, Shield, navigation, and view-data conventions.
- [x] 2.4 Add the guarded clear managed-cache action with confirmation, safe notifications, partial failure handling, and no arbitrary key/command input.
- [x] 2.5 Add Indonesian and English translations for page labels, status messages, action confirmation, and notifications.
- [x] 2.6 Add a separate guarded clear action for every declared managed cache group and report the selected group in the notification and audit metadata.
- [x] 2.7 Keep the global clear action in the page header, render the managed-cache description as the section description, and render per-group confirmation actions inside the section without a grid.

## 3. Authorization and Audit

- [x] 3.1 Seed `View:CacheManagement` and `Manage:CacheManagement` permissions through the existing Shield seeder.
- [x] 3.2 Enforce page access for superusers and users with the view permission.
- [x] 3.3 Enforce clear-action access for superusers and users with the management permission at execution time.
- [x] 3.4 Record successful and failed clear attempts through `AuditLogService` with safe scope, outcome, and existing correlation metadata only.

## 4. Verification

- [x] 4.1 Add feature tests for page overview, connected/unavailable status, secret redaction, and clear notifications.
- [x] 4.2 Add authorization tests for superusers, view-only users, management users, and unauthorized users.
- [x] 4.3 Add isolation tests proving a selected group invalidates only that group and queue, Horizon, session, rate limiter, and unrelated Redis data are not invalidated.
- [x] 4.4 Add regression tests that fail if cache management invokes `FLUSHALL`, `FLUSHDB`, arbitrary Redis commands, key scans, or arbitrary key deletion.
- [ ] 4.5 Run formatter, static analysis, relevant PHPUnit tests, full backend tests where available, and frontend/build verification.
- [x] 4.6 Validate the OpenSpec change and trace implementation/tests back to Issue #73 acceptance criteria.
