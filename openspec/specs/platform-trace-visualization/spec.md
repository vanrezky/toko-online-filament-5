# Platform Trace Visualization Specification

## Purpose

Lets platform engineers inspect persisted OpenTelemetry traces inside the Filament admin panel: listing and searching traces, viewing a truthful waterfall and span tree built from real span timestamps, inspecting sanitized span details, and drilling into related Integration and Audit logs.

## Requirements

### Requirement: List persisted traces
The system SHALL provide an admin page under the Observability cluster that lists persisted traces. Each row SHALL show the trace start timestamp, trace ID, correlation ID when present, root operation, status, total duration, span count, and error count. Traces SHALL be ordered by start time, most recent first. The page SHALL NOT load an unlimited number of traces; it SHALL paginate the result set.

#### Scenario: Trace list is rendered
- **WHEN** an authorized user opens the traces page and traces exist
- **THEN** the system SHALL display the traces ordered by start time descending, one row per trace, with the timestamp, trace ID, correlation ID, root operation, status, duration, span count, and error count

#### Scenario: Trace list is paginated
- **WHEN** the number of persisted traces exceeds the page size
- **THEN** the system SHALL load only the current page of traces and SHALL provide pagination to reach the remaining traces

### Requirement: Filter and search traces
The system SHALL allow filtering the trace list by time range, status, root operation, duration, presence of errors, and correlation ID, and SHALL allow searching by trace ID or correlation ID. Filters SHALL be applied at the backend query level.

#### Scenario: Filter by time range
- **WHEN** a user selects a time range filter
- **THEN** the system SHALL only show traces that started within the selected range

#### Scenario: Filter by status
- **WHEN** a user selects a status filter
- **THEN** the system SHALL only show traces whose status matches the selected status

#### Scenario: Filter by duration
- **WHEN** a user sets a duration filter
- **THEN** the system SHALL only show traces whose total duration satisfies the duration criterion

#### Scenario: Filter by error presence
- **WHEN** a user enables the error filter
- **THEN** the system SHALL only show traces that contain at least one failed span

#### Scenario: Search by trace ID or correlation ID
- **WHEN** a user enters a trace ID or correlation ID in the search box
- **THEN** the system SHALL only show traces matching the entered identifier

### Requirement: Show trace detail
The system SHALL provide a trace detail page reachable from a trace list row. The page SHALL show the root operation, trace ID, correlation ID when present, total duration, status, span count, and error count. The page SHALL load the trace by its trace ID.

#### Scenario: Trace detail is rendered
- **WHEN** an authorized user opens a specific trace
- **THEN** the system SHALL display the trace header with the root operation, trace ID, correlation ID, duration, status, span count, and error count

#### Scenario: Unknown trace ID
- **WHEN** a user opens a trace detail page for a trace ID that does not exist
- **THEN** the system SHALL show a not-found result rather than an empty or fabricated trace

### Requirement: Render a truthful waterfall
The system SHALL render a waterfall of spans for a trace where each span bar is positioned and sized from the span's actual start and end timestamps relative to the trace start. The system SHALL NOT fabricate spans or invent timings.

#### Scenario: Waterfall uses real span timings
- **WHEN** a trace detail page is rendered
- **THEN** each span SHALL be drawn at a horizontal offset and width computed from its actual start and end timestamps, and SHALL NOT contain any span that was not actually recorded

### Requirement: Show span hierarchy
The system SHALL render the spans of a trace as a hierarchy that reflects the recorded parent-child relationships, using the recorded parent span ID to nest child spans under their parent.

#### Scenario: Spans are nested by parent
- **WHEN** a trace contains spans with parent-child relationships
- **THEN** the system SHALL render child spans nested under their recorded parent span

### Requirement: Show span detail
The system SHALL provide per-span detail showing the trace ID, span ID, parent span ID, operation, start and end time, duration, status, sanitized attributes, and sanitized events. Attributes and events SHALL come only from the allow-listed set and SHALL NOT contain sensitive values.

#### Scenario: Span attributes and events are shown
- **WHEN** a user inspects a span detail
- **THEN** the system SHALL display the span's allow-listed attributes and events, including the span ID and parent span ID

#### Scenario: Span detail excludes sensitive data
- **WHEN** a span detail is rendered
- **THEN** the displayed attributes SHALL NOT include passwords, tokens, secrets, headers, bodies, raw SQL, or bound parameter values

### Requirement: Highlight slow spans
The system SHALL visually distinguish spans whose duration equals or exceeds a configured slow-span threshold. The threshold SHALL be configurable via configuration.

#### Scenario: Slow span is highlighted
- **WHEN** a span's duration is equal to or greater than the configured threshold
- **THEN** the span SHALL be visually marked as slow in the trace detail page

### Requirement: Highlight failed spans
The system SHALL visually distinguish spans whose status is failed, and the trace SHALL report its error count from the number of failed spans.

#### Scenario: Failed span is highlighted
- **WHEN** a span has a failed status
- **THEN** the span SHALL be visually marked as failed in the trace detail page and counted in the trace error count

### Requirement: Label partial traces honestly
The system SHALL label traces that are incomplete as partial. A trace SHALL be considered incomplete when any of its recorded spans has no end timestamp.

#### Scenario: Incomplete trace is labeled
- **WHEN** a trace contains a span without an end timestamp
- **THEN** the trace SHALL be labeled as a partial trace in the list and detail pages

### Requirement: Link traces to Integration and Audit logs
The system SHALL provide actions on a trace that carry a correlation ID to copy the correlation ID, to view the related Integration Logs, and, when supported, to view the related Audit Logs. Spans representing an external HTTP call SHALL link to their Integration Log when the log can be matched.

#### Scenario: Copy correlation ID
- **WHEN** a user uses the copy action on a trace that has a correlation ID
- **THEN** the correlation ID SHALL be copied to the clipboard

#### Scenario: View related Integration Logs
- **WHEN** a user triggers the view-related-Integration-Logs action on a trace with a correlation ID
- **THEN** the system SHALL navigate to the Integration Log list filtered to the correlation ID

#### Scenario: View related Audit Logs
- **WHEN** a user triggers the view-related-Audit-Logs action on a trace with a correlation ID
- **THEN** the system SHALL navigate to the Audit Log list filtered to the correlation ID when supported

#### Scenario: HTTP span links to its Integration Log
- **WHEN** a span represents an external HTTP call and a matching Integration Log exists
- **THEN** the span detail SHALL provide a link to the Integration Log
