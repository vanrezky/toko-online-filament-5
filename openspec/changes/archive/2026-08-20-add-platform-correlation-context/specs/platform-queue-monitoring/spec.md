## ADDED Requirements

### Requirement: Surface correlation ID on recent failed queue jobs
The Queue Monitor page SHALL display the correlation ID for recent failed jobs when the stored job payload carries one, without modifying Horizon itself.

#### Scenario: Recent failed job carries a correlation ID
- **WHEN** an authorized user opens the Queue Monitor page and a recent failed job's payload contains a correlation ID
- **THEN** the page SHALL display that correlation ID for the job

#### Scenario: Recent failed job has no correlation ID
- **WHEN** an authorized user opens the Queue Monitor page and a recent failed job's payload has no correlation ID
- **THEN** the page SHALL display the job without a correlation ID value