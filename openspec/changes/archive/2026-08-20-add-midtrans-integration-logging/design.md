## Context

See proposal.md — Why. The Midtrans PHP SDK (`Midtrans\Snap::getSnapToken`, `Midtrans\Transaction::status`) makes HTTP calls internally with no transport hook, so outbound evidence must be captured at the gateway method boundary. `IntegrationLogService` already provides the best-effort two-phase lifecycle (pending → success/failed), sanitization, payload bounds, correlation, and morph-to subject linking, as established by the courier and webhook instrumentation.

## Goals / Non-Goals

**Goals:**

- Wrap the two Midtrans outbound methods with the existing integration log lifecycle, capturing sanitized request payloads and SDK results without transport-level interception.
- Keep business contracts identical: same `PaymentResponse`/`PaymentStatus` return values and failure responses, no change to amounts, expiry, or gateway selection.
- Let status checks link the log to a `Transaction` subject when the order id resolves, without requiring a subject on every path.

**Non-Goals:**

- Replacing the Midtrans SDK or adding global HTTP/transport hooks.
- Instrumenting Stripe or Xendit gateways.
- Capturing raw headers/bodies the SDK does not expose.
- New schema, config, or admin UI changes.

## Decisions

### Wrap at the gateway method boundary, not the service level

Log inside `MidtransGateway::createPayment()` and `getPaymentStatus()` rather than in `PaymentGatewayService`. The gateway owns the request payload and the SDK result, so it is the only layer that can record a meaningful request/response pair. `PaymentGatewayService` remains a thin facade; a service-level wrapper could not capture the payload built by the SDK call.

Alternative considered: instrumenting `PaymentGatewayService`. Rejected because it would produce generic logs without the request payload or the SDK response, reducing troubleshooting value.

### Two-phase lifecycle with unchanged control flow

Each instrumented method: `IntegrationLogService::start()` before the SDK call and `finish()` with `success`/`failed` after it. Both methods already catch SDK exceptions and return a failure response (`PaymentResponse`/`PaymentStatus`); the log is completed as `failed` with the exception class and message inside those existing catch blocks, then the existing failure response is returned. Persistence is best-effort inside the service, so a logging outage never changes the gateway return value or control flow. The existing `try/catch` shape of each method is preserved; only log start/finish/fail calls are added inside the same blocks.

### Endpoint representation without transport URLs

The SDK hides transport URLs, so `url` uses the documented Midtrans API endpoints (`https://app.midtrans.com/snap/v1/transactions` / `https://app.sandbox.midtrans.com/snap/v1/transactions` for token creation, `/v2/{order_id}/status` for status) and `endpoint` uses the SDK method name for stable filtering. `method` reflects the underlying HTTP verb. `response_body` stores the Snap token for creation and the decoded SDK result object for status checks; secrets never reach these fields because the payloads are sanitized and the result objects contain no keys in the sensitive list.

### Subject resolution for status checks

`createPayment` always has the `Transaction`, so it links the subject directly. `getPaymentStatus` receives only an order id string; resolve `Transaction::query()->where('uuid', $transactionId)->first()` when available and leave the subject null otherwise. This mirrors the webhook controller's optional subject resolution.

## Risks / Trade-offs

- [SDK result objects may contain unexpected fields] → The sanitizer runs on the captured body before persistence; any unknown sensitive key can be added to `integration-logging.sensitive_keys` without code changes.
- [Response body does not include transport-level headers] → Acceptable trade-off; endpoint, method, status, duration, and sanitized payloads still cover the common failure modes.
- [Logging overhead on status-check polling] → Each log is bounded by the existing 64 KiB cap and pruned daily by the existing `integration-logs:prune` scheduler.

## Migration Plan

1. Deploy the changed `MidtransGateway` alongside the existing integration-logging code; no migration is required (reuses `integration_logs`).
2. Rollback is a code revert of the two instrumented methods; logs already collected can be pruned normally.