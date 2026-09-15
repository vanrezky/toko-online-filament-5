## Why

Issue #10: Midtrans transactions currently retain only the gateway value (`midtrans`). The actual payment channel, such as `qris`, and the relevant provider response are available only through integration logs, which makes transaction-level tracing difficult.

## What Changes

- Persist a normalized Midtrans payment channel in a transaction payment-response record when it is present in a verified webhook or status response.
- Persist sanitized, queryable Midtrans response data in dedicated transaction payment-response records for the same reconciliation paths.
- Preserve the existing integration logs, gateway verification, billing transitions, and inventory behavior.
- Add regression coverage for QRIS webhook data and payment-status reconciliation.

## Scope

The first implementation covers the current Midtrans checkout path. Stripe and Xendit are out of scope because they are not accepted by the current checkout validation path.

## Non-Goals

- Redesigning the integration-log viewer.
- Persisting credentials, signatures, or other sensitive secrets.
- Changing payment verification, billing rules, cancellation behavior, or inventory reservation.
