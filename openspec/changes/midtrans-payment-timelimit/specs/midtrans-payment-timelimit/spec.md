## ADDED Requirements

### Requirement: Administrators can configure a transaction limit
The system SHALL let an authorized administrator set one bounded transaction-limit duration of at least five minutes in Settings Commerce. The configured duration SHALL apply to every payment method and gateway for transactions created after the setting is saved and MUST NOT rewrite an existing transaction deadline.

#### Scenario: Administrator changes the duration
- **WHEN** an authorized administrator saves a new valid payment-limit duration
- **THEN** the next checkout uses that duration while existing transaction deadlines remain unchanged

### Requirement: Checkout persists timezone-safe deadline facts
The system SHALL require a valid IANA customer timezone for a new checkout, persist it for the customer, snapshot it on the transaction, and calculate the transaction `timelimit` as an absolute UTC instant using the configured duration. A missing timezone on legacy customer data MAY use the configured application timezone only as a compatibility fallback that is snapshotted on the new transaction.

#### Scenario: Customer starts checkout from a recognized timezone
- **WHEN** the customer submits a valid checkout with a valid IANA timezone
- **THEN** the transaction stores a UTC deadline and the same timezone snapshot used to create it

#### Scenario: Checkout submits an invalid timezone
- **WHEN** a checkout submits a timezone that is not a valid IANA identifier
- **THEN** the system rejects checkout without creating a transaction

### Requirement: Customer can see an informational payment deadline
The order-detail page SHALL display an eligible unpaid order's deadline and countdown using the transaction timezone snapshot. The browser MUST treat the deadline as informational and MUST NOT mutate transaction or billing state.

#### Scenario: Customer views a pending order
- **WHEN** the customer opens an eligible pending order before its deadline
- **THEN** the page shows the formatted timezone-aware deadline and a countdown to the stored UTC instant

#### Scenario: Browser reaches the displayed deadline
- **WHEN** the countdown reaches zero before server-side cancellation is reflected
- **THEN** the page refreshes order data and does not locally mark the order cancelled

### Requirement: Midtrans Snap uses the persisted transaction deadline
The system SHALL send Midtrans Snap `expiry` and `page_expiry` from the remaining duration to the stored transaction deadline whenever it creates a Snap token. It MUST NOT issue a Snap token with fewer than five remaining minutes.

#### Scenario: Customer opens a newly created Midtrans payment
- **WHEN** the system creates a Snap token for a pending Midtrans transaction with at least five minutes remaining
- **THEN** both Snap expiry payloads use the remaining transaction duration in whole minutes

#### Scenario: Customer resumes payment near expiry
- **WHEN** a customer requests a new Snap token with fewer than five minutes remaining
- **THEN** the system rejects the request and does not create a new Snap token
