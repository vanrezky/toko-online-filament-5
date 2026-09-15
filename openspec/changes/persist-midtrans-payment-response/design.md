## Context

Issue #10 concerns the gap between the provider value currently stored on `transactions.payment_method` and the actual Midtrans channel returned after payment. The application already has a bounded `IntegrationLogSanitizer`, and both Midtrans webhook and status paths already return gateway DTOs. The smallest coherent fix is to persist normalized payment data on the existing transaction instead of introducing payment-attempt records or a second tracing system.

## Design

### Transaction payment-response persistence

- Add a `transaction_payment_responses` table with `transaction_id`, `provider`, `source`, nullable `payment_channel`, JSON `response`, timestamps, and a unique transaction/provider/source key.
- Add a `TransactionPaymentResponse` model and a `Transaction::paymentResponses()` relation.
- Add one transaction model method that updates a source-specific response row (`webhook` or `status`) and only replaces its channel when a non-empty channel is supplied.

### Gateway metadata

- Extend `PaymentStatus` with optional metadata while preserving existing constructor calls.
- Keep `WebhookResult::$metadata` and add `payment_channel` plus a sanitized `payment_response` payload for valid Midtrans responses.
- In `MidtransGateway`, remove `signature_key` before using the existing `IntegrationLogSanitizer` and bounded capture behavior.

### Persistence points

- `PaymentWebhookController` records verified webhook metadata inside its existing row lock transaction before billing status branching.
- `OrderService::synchronizePaymentStatus` records status metadata after the gateway response is received; existing amount validation still gates billing updates.
- Integration logs remain unchanged and continue to store the detailed boundary request/response evidence.

### Compatibility and safety

- Existing `payment_method` keeps its current provider/business meaning.
- Response history is queryable through the transaction relation without adding response JSON to the main transaction row.
- No frontend contract or admin UI is changed in this issue.
- No raw credentials or webhook signatures are persisted.
- Existing response and status behavior remains the source of truth for payment processing.

## Alternatives Rejected

- A new payment-attempt table would preserve more history than Issue #10 requires and expand reconciliation behavior.
- Replacing integration logs would remove existing observability and duplicate the current bounded sanitization system.
