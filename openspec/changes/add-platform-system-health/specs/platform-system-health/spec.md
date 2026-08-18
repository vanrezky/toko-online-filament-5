## ADDED Requirements

### Requirement: Spatie-backed infrastructure health checks

The system SHALL use a Laravel-11-compatible `spatie/laravel-health` installation as the authoritative engine for application, database, Redis/cache, queue-processing, and disk-space health evaluation. It SHALL use package-provided checks when the installed package provides a relevant check.

#### Scenario: Core infrastructure is healthy

- **WHEN** application, database, Redis-backed cache, queue processing, and disk usage meet their configured thresholds
- **THEN** the health engine SHALL record or return healthy results for the required checks.

#### Scenario: Redis is unavailable

- **WHEN** the Redis/cache check cannot connect to Redis
- **THEN** the health result SHALL be failed or unknown with a safe technical message and SHALL NOT expose credentials or a stack trace.

#### Scenario: Queue processing is unavailable

- **WHEN** the package-supported queue check detects that its queue job is not processed within its configured expectation
- **THEN** the queue health result SHALL be failed with a safe message indicating that queue processing is unavailable.

### Requirement: Disk thresholds

The system SHALL use the installed package's disk-space check and SHALL default to warning at 70 percent used space and failure at 90 percent used space, while allowing environment/configuration overrides.

#### Scenario: Disk usage reaches warning threshold

- **WHEN** monitored disk usage is at or above the warning threshold and below the failure threshold
- **THEN** the disk health result SHALL be warning.

#### Scenario: Disk usage reaches failure threshold

- **WHEN** monitored disk usage is at or above the failure threshold
- **THEN** the disk health result SHALL be failed.

### Requirement: Package-supported scheduling and persistence

The system SHALL use the installed package's supported result persistence and scheduled execution mechanism when required for stored results or asynchronous queue checks. It SHALL register any required command in the existing Laravel scheduler and SHALL NOT add cron infrastructure.

#### Scenario: Scheduled health execution is due

- **WHEN** the existing Laravel scheduler invokes the registered health command at its configured cadence
- **THEN** the package SHALL execute or persist health results through its supported mechanism.

### Requirement: Authorized System Health dashboard

The Filament admin panel SHALL expose `System Health` in the `Platform` navigation group only to a superuser or a user granted the existing-style `View:SystemHealth` permission.

#### Scenario: Authorized technical user views System Health

- **WHEN** a superuser or a user granted `View:SystemHealth` visits System Health
- **THEN** the page SHALL display the latest safe overall status and required check summaries with a `Refresh Health` action.

#### Scenario: Unauthorized user requests System Health

- **WHEN** a user without superuser status or `View:SystemHealth` requests System Health
- **THEN** the system SHALL deny access and SHALL not display the page in navigation.

### Requirement: Safe normalized health summary

The system SHALL use `HealthMonitorService` as the boundary between Spatie Laravel Health and Filament. It SHALL normalize overall status and check results as healthy, warning, failed, or unknown; it SHALL expose only safe, whitelisted metadata and SHALL not run all checks repeatedly during a single page render.

#### Scenario: A current health result is available

- **WHEN** the package provides current or stored health results
- **THEN** HealthMonitorService SHALL return one normalized summary with overall status, check name, status, safe message, and safe metadata.

#### Scenario: Health results cannot be read

- **WHEN** the package runner or result store raises an infrastructure exception or has no result yet
- **THEN** HealthMonitorService SHALL return an unknown/unavailable summary and System Health SHALL render it without an exception.

#### Scenario: User manually refreshes health

- **WHEN** an authorized user activates `Refresh Health`
- **THEN** System Health SHALL invoke the package-supported health execution once and SHALL refresh the normalized summary without duplicating check configuration.
