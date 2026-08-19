## Why

Critical catalog, transaction, and installment-payment changes currently have no single, safe, read-only audit trail for technical administrators. This change establishes a selective Platform audit capability so accountability and business-state investigation do not require reconstructing changes from application records.

## What Changes

- Add `spatie/laravel-activitylog` as the audit engine and apply its required database migration.
- Add selective automatic audit coverage for approved critical Eloquent models, recording only meaningful fields and excluding sensitive data.
- Add a small Platform Audit boundary for activity normalization, safe metadata, query/filter support, and targeted explicit business activities.
- Add a read-only native Filament Audit Logs resource under the existing `Platform` navigation group, including filtering, search, activity detail, and readable field-level diffs.
- Add technical-access authorization using the established superuser-or-`View:<Page>` permission pattern.
- Add configurable retention guidance using the package-supported pruning command, without destructive pruning by default.

## Capabilities

### New Capabilities

- `platform-audit-logging`: Selective, secure business audit capture through Spatie Activitylog for approved models and explicit actions.
- `platform-audit-log-administration`: Read-only, authorized Filament discovery and inspection of Platform audit activities.

### Modified Capabilities

- None.

## Impact

- Adds the `spatie/laravel-activitylog` Composer dependency, package configuration, and `activity_log` persistence.
- Affects selected models (`Product`, `Transaction`, `InstallmentPayment`) and one or more targeted business-action call sites only; it does not introduce global model auditing.
- Adds `app/Modules/Platform/Audit`, a Filament audit resource/pages, localization entries, permission seeding, and focused feature/unit tests.
- Reuses Filament Shield and Spatie Permission. It does not modify Horizon, System Health, public API contracts, webhook logging, or the existing MVC architecture.
