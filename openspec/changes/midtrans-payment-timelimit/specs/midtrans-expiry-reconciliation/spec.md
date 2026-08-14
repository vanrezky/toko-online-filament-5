## ADDED Requirements

### Requirement: Only eligible unpaid transactions are automatically expired
The system SHALL consider a transaction eligible for automatic expiry only when it is packed, billing-pending, and its non-null UTC deadline is at or before current UTC time. This policy SHALL apply to every payment method and gateway; paid, cancelled, and non-pending billing contracts MUST NOT be expired by this feature.

#### Scenario: Expired balance transaction is scanned
- **WHEN** the expiry scan encounters a packed balance transaction with pending billing and a past deadline
- **THEN** it enqueues and cancels that transaction through the standard cancellation path

### Requirement: Expiry scan dispatches durable cancellation work
The scheduler SHALL run an expiry scan every minute on one server and dispatch eligible transaction IDs to a queued expiry job. The scan and job SHALL use the database deadline as the authoritative recovery source, and the job SHALL work with the application's configured queue connection including Redis.

#### Scenario: Worker misses a previously dispatched job
- **WHEN** an eligible order remains expired after a queue worker or broker interruption
- **THEN** a later scheduled scan dispatches cancellation work again without requiring a new checkout

### Requirement: Expiry cancellation is idempotent and settlement-safe
The queued expiry job SHALL lock and re-check the transaction, query the provider status before cancelling an external-gateway transaction, and cancel through the established cancellation service only when provider and local facts still prove it unpaid. A successful provider payment or a locally paid transaction MUST win over cancellation. Unknown provider status MUST cause retry without cancellation.

#### Scenario: Webhook settlement races with expiry job
- **WHEN** an external-gateway settlement webhook and an expiry job process the same transaction concurrently
- **THEN** the transaction is not cancelled if the provider or locked local transaction is paid

#### Scenario: Duplicate expiry jobs run
- **WHEN** more than one expiry job runs for the same eligible transaction
- **THEN** cancellation side effects and expiry notification occur at most once
