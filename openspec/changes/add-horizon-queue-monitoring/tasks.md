## 1. Horizon and Redis Runtime

- [x] 1.1 Add the Laravel-11-compatible `laravel/horizon` dependency and publish/register only its required configuration and provider assets.
- [x] 1.2 Set Redis as the configured queue connection, add Horizon environment supervisor configuration for the existing queues, and preserve failed-job configuration.
- [x] 1.3 Configure Horizon dashboard authorization using the existing superuser or `View:QueueMonitor` permission predicate.

## 2. Platform Queue Monitoring

- [x] 2.1 Create the isolated `app/Modules/Platform/Queue` namespace and implement `QueueMonitorService` with a normalized, fault-tolerant health snapshot backed by Horizon/Laravel queue APIs or contracts.
- [x] 2.2 Add Queue Monitor presentation components in Filament that consume the service, show the required high-level metrics and states, and link to Horizon without duplicating its UI.
- [x] 2.3 Register `Platform` navigation, use the existing Filament Shield page authorization pattern, and add required translation keys/permission generation or seeding integration.

## 3. Deployment and Operations

- [x] 3.1 Update local and deployment environment configuration to use Redis queues without changing domain job behavior.
- [x] 3.2 Update the Supervisor-oriented deployment/runbook instructions for `php artisan horizon`, graceful termination, restart, Redis prerequisites, queue migration handling, and rollback.

## 4. Verification

- [x] 4.1 Add focused tests for Queue Monitor authorization and the Horizon dashboard authorization predicate.
- [x] 4.2 Add service tests for healthy, empty, no-worker, and Redis/Horizon-unavailable snapshots using mocked/faked dependencies.
- [x] 4.3 Run Pint, focused tests, frontend build if presentation assets change, and OpenSpec validation; resolve all failures.
