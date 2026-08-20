## Purpose

Provide platform administrators a single, authorized, range-filtered Overview page that aggregates existing integration, queue, and health signals for fast operational assessment and correlation drill-down.

## ADDED Requirements

### Requirement: Authorized Observability Overview page
The Filament admin panel SHALL expose `Observability → Overview` in the `Platform` navigation group only to a superuser or a user granted the existing-style `View:Observability` permission. The page SHALL be read-only and SHALL expose no create, edit, or delete actions.

#### Scenario: Authorized user views Observability Overview
- **WHEN** a superuser or a user granted `View:Observability` visits Observability Overview
- **THEN** the page SHALL render the metric cards, provider performance, slow calls, and recent errors using data within the selected time range

#### Scenario: Unauthorized user requests Observability Overview
- **WHEN** a user without superuser status or `View:Observability` requests Observability Overview
- **THEN** the system SHALL deny access and SHALL not display the page in navigation

### Requirement: Time-range filter
The Observability Overview page SHALL provide a time-range filter with presets for the last 24 hours, last 7 days, and last 30 days, plus a custom date-range option. All displayed metrics SHALL be computed within the selected range and SHALL update when the range changes.

#### Scenario: Default range applies
- **WHEN** an authorized user opens Observability Overview without choosing a range
- **THEN** the page SHALL use a sensible default range (last 24 hours) and SHALL indicate the active range

#### Scenario: Preset range selected
- **WHEN** an authorized user selects the 7-day or 30-day preset
- **THEN** all cards and tables SHALL reflect only logs within that range

#### Scenario: Custom range selected
- **WHEN** an authorized user selects a custom date range
- **THEN** all cards and tables SHALL reflect only logs within the selected dates

### Requirement: Aggregate metric cards
The system SHALL compute and display aggregate integration metrics from `integration_logs` bounded by the selected range using aggregate queries, without loading full tables. The cards SHALL include at minimum total integration calls, failed integration calls, average duration, and slowest call duration. The system SHALL also display the existing queue failed-jobs count and health overall status from the existing snapshot services.

#### Scenario: Integration logs exist in range
- **WHEN** the selected range contains integration logs
- **THEN** the cards SHALL show total calls, failed calls, average duration, and slowest duration computed from those logs, plus the queue and health summaries

#### Scenario: No integration logs in range
- **WHEN** the selected range contains no integration logs
- **THEN** the integration metric cards SHALL display zero and the page SHALL render empty states instead of errors

### Requirement: Provider performance table
The system SHALL present a provider performance table SHOWING, per provider within the selected range, the call count, failed count, failed rate, and average duration, ordered for at-a-glance assessment.

#### Scenario: Providers have logs in range
- **WHEN** the selected range contains integration logs grouped by provider
- **THEN** each provider row SHALL show its call count, failed count, failed rate, and average duration

#### Scenario: No provider activity in range
- **WHEN** the selected range contains no integration logs
- **THEN** the provider table SHALL render an empty state

### Requirement: Slowest integration calls
The system SHALL present the slowest integration calls within the selected range as a bounded list ordered by duration descending, including provider, endpoint, duration, status, and correlation ID.

#### Scenario: Slow calls exist
- **WHEN** the selected range contains integration logs with a measured duration
- **THEN** the slowest calls SHALL be listed ordered by duration descending with provider, endpoint, duration, status, and correlation ID

#### Scenario: No calls with duration in range
- **WHEN** the selected range contains no integration logs with a measured duration
- **THEN** the slow-calls section SHALL render an empty state

### Requirement: Recent integration errors
The system SHALL present the most recent failed integration logs within the selected range as a bounded list including provider, endpoint, error class or message, timestamp, and correlation ID.

#### Scenario: Recent failures exist
- **WHEN** the selected range contains failed integration logs
- **THEN** the most recent failures SHALL be listed with provider, endpoint, error details, timestamp, and correlation ID

#### Scenario: No failures in range
- **WHEN** the selected range contains no failed integration logs
- **THEN** the recent-errors section SHALL render an empty state

### Requirement: Correlation ID drill-down
Each row in the observability tables SHALL expose its correlation ID, and activating it SHALL open the existing Integration Logs resource filtered to logs sharing that correlation ID.

#### Scenario: Admin follows a correlation ID
- **WHEN** an authorized user activates a correlation ID on a listed integration call
- **THEN** the system SHALL navigate to the Integration Logs resource showing only logs with that correlation ID

### Requirement: Aggregation bounded by existing indexes
The system SHALL compute all observability metrics using aggregate queries (`COUNT`/`AVG`/`MAX`/`GROUP BY`) with range bounds and explicit limits. It SHALL rely on the existing `integration_logs` indexes (`created_at`, `provider`/`status`, `correlation_id`) and SHALL NOT introduce new database schema or new logging mechanisms.

#### Scenario: Observability queries run
- **WHEN** an authorized user views Observability Overview
- **THEN** the backing queries SHALL be bounded aggregate queries with explicit limits and SHALL NOT perform full-table loads