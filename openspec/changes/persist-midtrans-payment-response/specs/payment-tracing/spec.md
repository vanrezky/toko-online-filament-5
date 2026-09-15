## ADDED Requirements

### Requirement: Persist the actual Midtrans payment channel

The system SHALL retain the gateway provider in `transactions.payment_method` and SHALL persist the actual Midtrans channel in a transaction payment-response record when a verified webhook or payment-status response provides one.

#### Scenario: QRIS webhook identifies the channel

- **WHEN** a valid Midtrans webhook for a transaction contains `payment_type: qris`
- **THEN** the transaction's payment-response record SHALL store `payment_channel: qris`
- **AND** the existing billing transition and webhook response SHALL remain unchanged

#### Scenario: A response has no channel

- **WHEN** a valid gateway response does not contain a payment channel
- **THEN** the existing payment-response channel value SHALL be preserved
- **AND** payment processing SHALL continue without failure

### Requirement: Persist sanitized gateway response data

The system SHALL persist the latest relevant Midtrans response data in a dedicated transaction payment-response table, grouped by provider and response source (`webhook` or `status`). Each record SHALL contain the transaction, provider, source, optional payment channel, and response JSON. The stored data SHALL use the existing integration-log sanitization and payload-size limits and SHALL not include the webhook signature or gateway credentials.

#### Scenario: Verified webhook response is stored

- **WHEN** a Midtrans webhook passes signature validation and is associated with an existing transaction
- **THEN** the sanitized webhook payload SHALL be stored in the transaction's webhook response record
- **AND** repeated webhooks SHALL update that transaction/provider/source record without creating duplicate response rows

#### Scenario: Payment status response is stored

- **WHEN** the payment-return flow receives a Midtrans status response for a transaction
- **THEN** the sanitized status response SHALL be stored in the transaction's status response record
- **AND** the existing amount verification SHALL still determine whether billing status may change

### Requirement: Preserve existing behavior

The change SHALL not alter Midtrans signature verification, amount validation, billing status mapping, cancellation, inventory behavior, or integration-log persistence.

#### Scenario: Invalid webhook remains rejected

- **WHEN** a Midtrans webhook has an invalid signature or missing required fields
- **THEN** it SHALL retain the current rejection behavior
- **AND** it SHALL not persist unverified payment response data on a transaction

#### Scenario: Existing non-Midtrans payments remain compatible

- **WHEN** a transaction uses balance, installment, or other existing payment values
- **THEN** its existing payment fields and behavior SHALL remain unchanged
