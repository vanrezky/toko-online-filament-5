# Platform Trace Service Map Specification

## Purpose

Provides a read-only service map in the admin panel that reflects the services and operations recorded in actual persisted traces, with metrics aggregated at the database level so platform engineers can see request volume, error rate, and latency across selectable time ranges.

## Requirements

### Requirement: Render a read-only service map
The system SHALL render a service map derived from the actual relationships recorded in persisted traces. The nodes and edges SHALL correspond to real recorded services and operations; the system SHALL NOT render hardcoded or fabricated nodes or edges.

#### Scenario: Service map reflects recorded data
- **WHEN** an authorized user opens the service map and persisted traces exist
- **THEN** the system SHALL render nodes and edges that are derived from the stored trace relationships

#### Scenario: Service map is read-only
- **WHEN** an authorized user opens the service map
- **THEN** the map SHALL NOT provide any editing, re-layout persistence, or workflow configuration controls

### Requirement: Aggregate service metrics at the backend
The system SHALL compute per-service and per-operation metrics at the database or backend level for the selected time range: request count, error rate, average duration, and P95 duration. Metrics SHALL be displayed only when they can be calculated from recorded spans.

#### Scenario: Metrics are aggregated from recorded spans
- **WHEN** a time range is selected and recorded spans exist
- **THEN** the system SHALL show the request count, error rate, average duration, and P95 duration computed from those spans

#### Scenario: Metrics are hidden when not calculable
- **WHEN** a metric cannot be calculated for a service or operation
- **THEN** the system SHALL not display a value for that metric rather than showing a fabricated value

### Requirement: Select time range
The system SHALL provide time range presets of the last 1 hour, last 24 hours, and last 7 days for the service map. Changing the range SHALL recompute the map and metrics from recorded spans within that range.

#### Scenario: Time range preset selection
- **WHEN** a user selects the last 24 hours preset
- **THEN** the system SHALL recompute the service map and metrics from spans recorded in the last 24 hours

### Requirement: Bound the data loaded
The system SHALL aggregate the service map at the database level and SHALL NOT load unlimited trace rows into memory to compute it.

#### Scenario: Aggregation happens in the backend
- **WHEN** the service map is rendered for a large number of spans
- **THEN** the system SHALL compute the map and metrics with backend aggregation rather than loading all spans at once
