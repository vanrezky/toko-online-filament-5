## 1. Package and persistence setup

- [x] 1.1 Install `spatie/laravel-activitylog`, inspect the resolved package version and its official published migration/configuration requirements.
- [x] 1.2 Publish only required Activitylog assets, review the generated `activity_log` schema and package indexes, then run the required migration.
- [x] 1.3 Add environment-backed, documented 180-day retention configuration with pruning disabled by default, using the package-supported prune command only when explicitly enabled.

## 2. Platform Audit capture boundary

- [x] 2.1 Create the minimal `App\Modules\Platform\Audit` service, support, and concern structure needed for explicit activity creation, safe metadata, and presentation normalization.
- [x] 2.2 Implement allow-listed, length-bounded HTTP request metadata capture and no-context behavior for console/queue/scheduler execution.
- [x] 2.3 Add selective package activity options to `Product`, `Transaction`, and `InstallmentPayment`, including dirty-only changes, meaningful descriptions, and sensitive/noisy attribute exclusions.
- [x] 2.4 Add one targeted explicit business audit point only where a generic model event is insufficient, ensuring its transition does not also create a duplicate automatic activity.

## 3. Filament administration and authorization

- [x] 3.1 Add `View:AuditLogs` to the existing Shield permission seeder without creating a new role.
- [x] 3.2 Create a native read-only Filament Audit Logs resource with list and view routes under the Platform navigation group and no mutation actions/pages.
- [x] 3.3 Implement superuser-or-`View:AuditLogs` access checks consistent with Queue Monitor and System Health.
- [x] 3.4 Implement indexed/package-native table columns, actor/event/subject/date filters, constrained search, and a readable activity detail/diff view with System and empty-property states.
- [x] 3.5 Add the required admin localization strings following existing project conventions.

## 4. Verification

- [x] 4.1 Add focused tests for selected-model activities, allow-listed old/new values, sensitive-field exclusion, authenticated causer, null-causer system activity, and duplicate-transition prevention.
- [x] 4.2 Add authorization and read-only Filament feature tests for superuser, `View:AuditLogs`, and unauthorized users.
- [x] 4.3 Run focused PHPUnit tests, formatting/static analysis appropriate to changed files, frontend build if assets change, and OpenSpec validation; resolve failures.
