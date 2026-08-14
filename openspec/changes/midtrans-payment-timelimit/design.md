## Context

Issue #36 adds a financial deadline to the existing Midtrans Snap flow. `transactions.timelimit` is already a nullable datetime, but checkout assigns it to `now()->addDay()` for every payment contract, customers do not have a timezone, and the existing 15-minute command cancels any packed transaction with pending billing. That is incompatible with internal credit orders, whose pending billing is legitimate.

The current Midtrans webhook and cancellation service already establish transaction locking and idempotent inventory/voucher handling. The new expiry path must share those boundaries and account for the fact that a provider settlement can precede its webhook delivery.

## Goals / Non-Goals

**Goals:**

- Configure an administrator-owned transaction-limit duration for every future order.
- Preserve a customer IANA timezone and transaction timezone snapshot while comparing deadlines as UTC instants.
- Show the customer an accurate deadline and countdown without trusting the browser to cancel an order.
- Cancel eligible pending orders through an idempotent queued job that is safe with gateway notification reconciliation.
- Support Redis as the configured Laravel queue broker while retaining the database transaction as the source of truth.

**Non-Goals:**

- Cancel transactions that are already paid or cancelled.
- Change Midtrans dashboard expiry policy, refund settled payments, or use browser time as a financial authority.
- Retroactively change existing transaction deadlines or customer timezone data.

## Decisions

### Store an absolute deadline plus an IANA timezone snapshot

Add nullable `timezone` to customers and nullable `customer_timezone` to transactions. On checkout, the frontend supplies `Intl.DateTimeFormat().resolvedOptions().timeZone`; the backend validates it against PHP's IANA timezone list, stores it on the customer, snapshots it to the transaction, and creates `timelimit` as `now('UTC')->addMinutes(transaction_time_limit_minutes)`. The stored deadline is therefore an absolute UTC instant, independent of PHP, MySQL, worker, or browser default timezone.

Existing customers without a stored timezone use the application's configured timezone only as a documented compatibility fallback; the fallback is also snapshotted. A raw browser offset is rejected because it has no daylight-saving history and cannot support auditable display.

### Configure duration in Settings Commerce and apply it prospectively

Add `transaction_time_limit_minutes` to `GeneralSettings`, its settings migration, and `ManageCommerceSettings`. Validate a bounded positive minute duration and default it to 1,440 to preserve the existing one-day behavior for new orders. Changing the setting does not rewrite already-created deadlines, so every transaction remains auditable against the policy in effect at checkout.

### Apply expiry eligibility to every unpaid transaction

An eligible row must have `status=packed`, `billing_status=pending`, and a non-null deadline at or before current UTC time. Every payment method and gateway uses this policy; paid, cancelled, and not-applicable billing contracts are excluded. External gateway methods receive a provider-status reconciliation before cancellation.

### Use a scheduled dispatcher and an idempotent queued cancellation job

The scheduler runs the expiry scan every minute on one server. It finds eligible expired transaction IDs and dispatches one `ExpireTransaction` job per ID using Laravel's configured queue connection; production may select Redis without coupling financial correctness to a delayed Redis message.

The job locks and reloads the transaction, re-evaluates eligibility and UTC deadline, queries a supported external provider before cancelling, and treats a provider success/settlement as paid reconciliation rather than cancellation. If provider status cannot be confirmed, the job releases/retries rather than cancelling on uncertainty. The job then invokes the existing `TransactionCancellationService` exactly once and dispatches expiry notification only after successful cancellation. Duplicate scans/jobs become no-ops.

The alternative of cancelling directly from the scheduler leaves long-running cancellation work in a cron process and provides weaker retry/observability. The alternative of Redis delayed jobs as the only trigger risks missed cancellation after worker/broker downtime; the periodic database scan remains the recovery mechanism.

### Display deadline from the transaction snapshot; browser countdown is informational

`OrderResource` exposes the UTC deadline and timezone snapshot. `Orders/Show.vue` formats the deadline using `Intl.DateTimeFormat` with the snapshot timezone, renders a countdown based on the deadline instant, and reloads when it reaches zero so the backend state is refreshed. It disables the Midtrans resume action when locally expired, but only server-side job/webhook reconciliation changes the status.

## Risks / Trade-offs

- [Browser does not provide an IANA timezone] → reject a new checkout with a clear validation error rather than create an ambiguous deadline.
- [Webhook arrives after the deadline] → the expiry job checks external-provider status and transaction locking; paid local/provider state wins over cancellation.
- [External provider status API is unavailable] → retry the job and keep the order pending rather than cancel a potentially paid order.
- [Queue or Redis is unavailable] → scheduled scan continues on the configured queue once available; metrics/logging identify failures and the database deadline prevents silent loss.
- [Customer changes timezone after checkout] → use the transaction snapshot for the historical deadline display.

## Migration Plan

1. Deploy schema and settings migration with the default 1,440-minute duration.
2. Deploy code before enabling the per-minute scheduler and queue worker.
3. Configure Redis queue connection where applicable and verify worker/scheduler health.
4. Exercise external-gateway pending, settlement, expiry, duplicate-job, and delayed-webhook scenarios.
5. Roll back by disabling the scheduler entry; existing deadlines remain stored and no automatic cancellation continues until the feature is re-enabled.

## Open Questions

- None. Issue #36 applies automatic expiry to every packed transaction with pending billing and a past deadline.
