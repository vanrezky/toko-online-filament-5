## ADDED Requirements

### Requirement: Record outbound Midtrans payment gateway calls
The system SHALL record outbound Midtrans SDK calls for payment creation and transaction status checks as outbound API integration logs. Each log SHALL carry the provider `midtrans`, the direction `outbound`, the API type, a sanitized request body, the SDK result as the response body, an HTTP-independent status outcome, and the `Transaction` subject when one is available. Business behavior SHALL be preserved: the same return values and failure responses are produced as today.

#### Scenario: Snap token creation succeeds
- **WHEN** `createPayment` returns a Snap token for a transaction
- **THEN** an outbound integration log is recorded as successful with the sanitized Snap request payload, the returned token, and the transaction as subject

#### Scenario: Snap token creation throws
- **WHEN** `createPayment` throws an exception while creating a Snap token
- **THEN** the existing failure response is returned unchanged and an outbound integration log is recorded as failed with the exception class and message

#### Scenario: Status check returns a result
- **WHEN** `getPaymentStatus` returns a status result for an order
- **THEN** an outbound integration log is recorded as successful with the order id, the SDK status result, and the transaction as subject when it can be resolved

#### Scenario: Status check throws
- **WHEN** `getPaymentStatus` throws an exception while checking a transaction status
- **THEN** the existing failure response is returned unchanged and an outbound integration log is recorded as failed with the exception class and message