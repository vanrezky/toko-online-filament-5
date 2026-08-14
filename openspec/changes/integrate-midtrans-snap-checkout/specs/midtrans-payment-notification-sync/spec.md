## ADDED Requirements

### Requirement: Midtrans notification is authenticated before local mutation
The system SHALL accept Midtrans payment notifications only at the Midtrans webhook route when Midtrans is active. It MUST verify the SHA-512 signature calculated from `order_id`, `status_code`, `gross_amount`, and the configured Server Key using a timing-safe comparison before changing local state.

#### Scenario: Signed Midtrans notification arrives
- **WHEN** the webhook receives a notification with a valid Midtrans signature for an existing transaction
- **THEN** it continues to local transaction validation and status reconciliation

#### Scenario: Forged or malformed notification arrives
- **WHEN** the webhook receives a notification with a missing field or invalid signature
- **THEN** it rejects the request and does not mutate any transaction

### Requirement: Payment outcome must match trusted local facts
The system SHALL confirm that a notification belongs to an existing Midtrans transaction and that its gross amount equals the local transaction total before applying an outcome. `capture` or `settlement` MUST be treated as successful only with a successful status code and an acceptable fraud status when one is supplied.

#### Scenario: Settled payment matches local total
- **WHEN** a signed settlement notification has a matching local total and successful payment fields
- **THEN** the system marks the transaction billing status as paid

#### Scenario: Signed notification amount differs from the order
- **WHEN** a signed notification has a gross amount different from the local transaction total
- **THEN** the system rejects it and preserves the existing local billing and order state

### Requirement: Notification reconciliation is idempotent and monotonic
The system SHALL process duplicate notifications as no-ops and MUST NOT regress a paid or cancelled transaction. Pending notifications MUST NOT reopen a paid transaction; failed notifications MUST NOT override a paid transaction; and expiry or cancellation MUST invoke the existing cancellation flow at most once for an eligible unpaid transaction.

#### Scenario: Duplicate settlement notification arrives
- **WHEN** the system receives the same valid settlement notification more than once
- **THEN** the transaction remains paid and no duplicate cancellation, inventory, voucher, or financial side effect occurs

#### Scenario: Delayed pending notification follows settlement
- **WHEN** a valid pending notification arrives after the local transaction is already paid
- **THEN** the system acknowledges it without changing the paid state

#### Scenario: Valid expiration notification arrives for an unpaid transaction
- **WHEN** a valid expire or cancel notification arrives for an eligible unpaid Midtrans transaction
- **THEN** the system invokes the established transaction-cancellation flow once and acknowledges duplicate deliveries without repeated side effects
