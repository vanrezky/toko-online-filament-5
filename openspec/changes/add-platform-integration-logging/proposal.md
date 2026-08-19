## Why

Technical administrators cannot currently inspect the application's communication with external providers in a structured, safe way. Midtrans payment webhooks and Api.co.id courier-shipping requests need boundary-level evidence that supports troubleshooting without persisting secrets or changing existing business error and retry behavior.

## What Changes

- Add a Platform Integration module and `integration_logs` persistence for sanitized inbound webhook and outbound API executions.
- Add a reusable, best-effort integration logger with correlation IDs, optional business subjects, duration measurement, bounded payload storage, and centralized recursive sensitive-data masking.
- Instrument Api.co.id courier-shipping cost requests as the initial outbound integration and Midtrans payment webhooks as the initial inbound integration.
- Add a read-only, permission-protected Filament Integration Logs resource under Platform, with troubleshooting-oriented table, filters, search, and sanitized detail views.
- Add configurable scheduled retention pruning for integration logs.

## Capabilities

### New Capabilities

- `platform-integration-logging`: Records sanitized, bounded, correlated inbound and outbound integration-boundary activity and maintains it safely.
- `platform-integration-log-visibility`: Provides authorized technical users a read-only Filament interface for troubleshooting integration activity.

### Modified Capabilities

- None.

## Impact

- New Platform Integration module, model, migration, configuration, pruning command, Filament resource, translations, and tests.
- `App\Services\ApicoidOngkirService` gains targeted outbound request logging while retaining Laravel HTTP Client and its existing response behavior.
- `App\Http\Controllers\PaymentWebhookController` gains targeted Midtrans inbound lifecycle logging while retaining signature validation, transaction handling, and HTTP responses.
- `Database\Seeders\ShieldSeeder` gains the existing-authorization-pattern permission `View:IntegrationLogs`.
