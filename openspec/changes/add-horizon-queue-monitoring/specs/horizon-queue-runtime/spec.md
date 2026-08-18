## ADDED Requirements

### Requirement: Redis-backed Horizon queue runtime

The system SHALL install a Laravel-11-compatible Laravel Horizon package, configure Redis as the application queue connection, and define at least one Horizon supervisor for the `default` queue without changing existing job business logic.

#### Scenario: Queue dispatch uses Redis

- **WHEN** an existing application job is dispatched with the normal queue API after the Redis queue configuration is active
- **THEN** the job SHALL be made available to a Redis-backed Horizon supervisor on its configured queue.

#### Scenario: Existing queue configuration remains compatible

- **WHEN** failed-job records or non-default queue connection configuration are used
- **THEN** the existing failed-job provider and explicit non-default connections SHALL remain configured unless this change explicitly replaces them.

### Requirement: Protected Horizon dashboard

The system SHALL restrict the Horizon dashboard to an authenticated user who is a superuser or has the existing `View:QueueMonitor` permission.

#### Scenario: Authorized technical user opens Horizon

- **WHEN** a superuser or user granted `View:QueueMonitor` requests the Horizon dashboard
- **THEN** the system SHALL allow access to the Horizon dashboard.

#### Scenario: Unauthorized user opens Horizon

- **WHEN** an unauthorised or unauthenticated user requests the Horizon dashboard
- **THEN** the system SHALL deny access and SHALL not expose Horizon details.

### Requirement: Supervisor-compatible Horizon operation

The system SHALL document Horizon execution as a long-running process managed by the existing Supervisor-oriented deployment model and SHALL not require a replacement orchestration platform.

#### Scenario: Production operator deploys queue runtime

- **WHEN** an operator deploys the Horizon-enabled application
- **THEN** the documented procedure SHALL identify Redis environment requirements, the Horizon Supervisor command, and the graceful restart procedure.
