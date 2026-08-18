## ADDED Requirements

### Requirement: Authorized Queue Monitor summary

The Filament admin panel SHALL expose a `Queue Monitor` page in the `Platform` navigation group only to a superuser or user granted `View:QueueMonitor` through the existing authorization system.

#### Scenario: Authorized user views Queue Monitor

- **WHEN** a superuser or user granted `View:QueueMonitor` visits Queue Monitor
- **THEN** the system SHALL render the operational queue summary and an `Open Horizon` action.

#### Scenario: Unauthorized user requests Queue Monitor

- **WHEN** a user without superuser status or `View:QueueMonitor` requests Queue Monitor
- **THEN** the system SHALL deny access and SHALL not display the page in authorized navigation.

### Requirement: High-level queue health metrics

The Queue Monitor SHALL present Horizon/worker status, total pending jobs, total failed jobs, processed jobs when available, active queues, and workload when available. It SHALL link to Horizon for detailed inspection and SHALL NOT clone Horizon controls or job detail views.

#### Scenario: Healthy Horizon workload is available

- **WHEN** Horizon and at least one worker are running with queue metrics available
- **THEN** Queue Monitor SHALL display a running status, the available counts, active queue names, workload information, and an `Open Horizon` action.

#### Scenario: Queue is empty

- **WHEN** Horizon is reachable and no pending jobs exist
- **THEN** Queue Monitor SHALL display zero pending jobs and a readable empty/no-work state without an error.

### Requirement: Fault-tolerant metrics boundary

The system SHALL obtain Queue Monitor metrics through `QueueMonitorService` in the Platform Queue area using Horizon or Laravel Queue APIs/contracts before direct Redis access. The Filament page and widgets SHALL NOT perform infrastructure metric queries directly.

#### Scenario: Redis is unavailable

- **WHEN** Redis or Horizon metric access throws a connection or infrastructure exception
- **THEN** QueueMonitorService SHALL return a safe unavailable/offline snapshot and Queue Monitor SHALL render it without an exception.

#### Scenario: No Horizon workers are running

- **WHEN** Redis is reachable but Horizon reports no active workers
- **THEN** QueueMonitorService SHALL return a no-workers or offline status and Queue Monitor SHALL render zero or unavailable metric values without an exception.
