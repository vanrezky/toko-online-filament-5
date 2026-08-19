## Why

Midtrans outbound SDK calls — Snap token creation (`createPayment`) and transaction status checks (`getPaymentStatus`) — currently leave no persisted troubleshooting evidence. Only the inbound webhook and the courier outbound call are recorded in `integration_logs`. The integration-logging design explicitly deferred Midtrans SDK outbound instrumentation as a separate targeted change because the SDK offers no transport hook; this change closes that gap so payment boundary failures are traceable end to end.

## What Changes

- Instrument `MidtransGateway::createPayment()` to record an outbound API integration log: sanitized Snap request payload, endpoint, returned Snap token as the response, status, duration, correlation ID, and the `Transaction` subject.
- Instrument `MidtransGateway::getPaymentStatus()` to record an outbound API integration log: order id, SDK status result as the response, status, duration, correlation ID, and the `Transaction` subject when resolvable.
- Preserve existing business behavior exactly: same return values and `PaymentResponse`/`PaymentStatus` contracts, exceptions still rethrown, no change to payment amounts, expiry, or gateway selection.
- Reuse the existing `IntegrationLogService`, `IntegrationLogSanitizer`, and correlation context; no new tables or configuration.

## Capabilities

### New Capabilities

(none)

### Modified Capabilities

- `platform-integration-logging`: add a requirement that outbound Midtrans payment gateway calls (payment creation and transaction status checks) are recorded as outbound API integration logs with sanitized payloads and linked to the transaction subject.

## Impact

- `app/Services/Gateways/MidtransGateway.php` — wraps `createPayment()` and `getPaymentStatus()` with the integration log lifecycle.
- `app/Modules/Platform/Integration/*` — unchanged (consumed as-is).
- Tests — new feature tests under `tests/Feature/Platform/Integration/` covering success, failure/rethrow, sanitization, correlation, and subject linking.
- No schema, config, or API changes.