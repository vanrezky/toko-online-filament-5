## Context

Issue #47 introduces an audit trail in a Laravel 11 / Filament 5 modular-monolith application. The repository already isolates operational integrations in `app/Modules/Platform/{Queue,Health}`, uses Spatie Permission plus Filament Shield, and grants technical surfaces to a superuser or a targeted `View:<Page>` permission. There is no audit package, `activity_log` table, or Activity model today.

`Product`, `Transaction`, and `InstallmentPayment` are the initial critical Eloquent subjects. `Transaction` is the order aggregate in this codebase; `InstallmentPayment` is the relevant payment record. `GeneralSettings` is Spatie Settings rather than Eloquent, and package-managed Role/Permission relation changes require separate, deliberately targeted behavior. Existing Horizon and Health records are operational telemetry and are excluded.

## Goals / Non-Goals

**Goals:**

- Use Spatie Activitylog as the sole audit persistence engine and keep Filament as a presentation layer.
- Capture a limited, high-value set of model changes with actor, subject, old/new values, and safe request context.
- Provide a small Platform Audit service boundary for querying, normalization, and explicit business activity.
- Provide a performant, read-only, authorized technical UI.
- Make retention configurable without deleting historical data merely because the package is installed.

**Non-Goals:**

- Global Eloquent auditing, request/webhook logging, tracing, or changes to Horizon and Health.
- A new role, generic repository layer, second Laravel application, or MVC refactor.
- Automatic auditing of `activity_log`, failed jobs, health results, Horizon metrics, Spatie Settings, or all role/permission pivot mutations.

## Decisions

### Use Spatie Activitylog package events and properties

Install the Composer-selected package release, publish only its required migration/configuration, and use its documented `LogsActivity`, `LogOptions`, `activity()`, and pruning APIs for that installed release. The package owns the `activity_log` schema, polymorphic causer/subject, and old/new property persistence.

Alternative considered: a custom audit table, observer, and serializer. Rejected because it duplicates mature package behavior and makes retention/UI queries harder to maintain.

### Selective model policy and explicit business activities

Initial automatic coverage is limited to:

- `Product`: name/code, category/warehouse assignment, stock/security stock, weight, price/sale/reseller price, minimum order, and active state.
- `Transaction`: payment/fulfillment state and reference fields (`payment_type`, `payment_method`, `billing_status`, `status`, receipt/delivery/completion/cancellation state, shipping/COD amounts).
- `InstallmentPayment`: installment reference, amount/paid amount, due/paid dates, payment/collection/payroll status, and confirmation state.

Each subject configures a package `LogOptions` allow-list, dirty-only updates, and the event set deliberately. All other attributes—including free-form notes and any credentials—are excluded. The design must avoid duplicate activities: a business transition receives either the automatic model activity with a meaningful description or a targeted explicit activity, not both for the same action.

An `App\Modules\Platform\Audit\Services\AuditLogService` provides an intentional package boundary for listing/normalizing activities and explicit actions that need business context. A lightweight concern and metadata support class may be shared by the selected models to add safe request context through the package tap hook. They do not reimplement package persistence.

Alternative considered: attaching `LogsActivity` to every business model. Rejected because it creates noisy high-volume logs and risks sensitive/low-value attribute capture.

### Safe actor and request context

Package causer resolution is retained for authenticated actions. Null causers are valid and normalize to `System` in the UI. The shared metadata helper captures only IP, request method, request URL/path, and a length-bounded user agent when there is an HTTP request; console, queue, and scheduler activities have no fabricated actor/context. It never copies payloads, headers, cookies, session data, or authentication credentials.

Alternative considered: creating a System user. Rejected because it obscures the absence of a real actor and weakens audit semantics.

### Read-only Filament resource with service-backed normalization

Add a native `Activity` resource under `Platform`, with List and View pages only. It has no create, edit, delete, bulk-delete, or relation mutation actions. Table filters query package-native causer, event, subject type, and created timestamp columns. Search uses indexed/low-cost columns where possible and relationship search only for actor name/email. `AuditLogService` maps package properties into an ordered field-level diff for the View page and returns a clear empty state when properties are absent.

The resource uses the existing technical-access rule directly: superuser or `View:AuditLogs`. `ShieldSeeder` creates that permission; no role is created.

Alternative considered: a third-party Filament audit plugin. Rejected because Filament's native resource/table already covers presentation needs and avoids a second audit abstraction.

### Retention is opt-in and environment-configured

Expose audit retention days in the package/config integration with a conservative documented default of 180 days. Add a scheduler integration only behind an explicit `AUDIT_LOG_PRUNING_ENABLED=false` default, invoking the package-supported pruning command for the installed release. No pruning runs in local, queue, scheduler, or deployment environments until the operator enables it deliberately.

Alternative considered: hardcoding a 90-day daily cleanup. Rejected because retention is a business/compliance decision and automatic deletion is destructive.

### Database indexes are package-first

Retain and inspect indexes provided by Spatie's migration before adding any migration. The Activity resource's main filters rely on `created_at`, `event`, and package polymorphic causer/subject indexes. A composite index is only added after schema inspection demonstrates an uncovered primary query path; there is no speculative indexing in the initial change.

## Risks / Trade-offs

- [Audit volume grows with selected events] → Limit subjects/attributes, enable dirty-only logging, and provide opt-in pruning.
- [Request metadata can expose personal/operational information] → Store only allow-listed scalar context, limit user-agent length, and never store payload/header/session data.
- [Automatic and explicit logging can duplicate one business action] → Identify the selected transaction transitions and choose one logging path per transition in tests.
- [Package version API differs] → Pin implementation to the Composer-resolved version and validate package docs/configuration after installation.
- [Audit UI queries can become expensive] → Use package schema indexes, date range filters, pagination, and constrained relationship search.

## Migration Plan

1. Install the package and publish only required assets for the resolved version.
2. Review the generated migration/indexes and run it in the normal deployment migration step.
3. Deploy config with pruning disabled; audit writes begin only for the selected models/actions.
4. Seed `View:AuditLogs` in the established Shield seeder and grant it through existing role administration when needed.
5. Validate the native Filament page with a superuser and permission-granted user.

Rollback consists of removing access/navigation and disabling audit writes in a follow-up deploy; existing audit rows are retained. The package migration is not rolled back in production merely to erase audit history.

## Open Questions

- None blocking implementation. The initial retention default is 180 days with pruning disabled; operators retain final control through environment configuration.
